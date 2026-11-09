'use client';

import { useCallback, useEffect, useState } from 'react';
import Pagination from '@/components/Pagination';
import { useRequireAuth } from '@/context/AuthContext';
import {
  errorMessage,
  formatPrice,
  getSessionFilters,
  listSessions,
  Paginated,
  reserveSession,
  SessionFilterOptions,
  TestSession,
} from '@/lib/api';

export default function SessionsPage() {
  const { user, loading } = useRequireAuth();
  const isAdmin = !!user?.roles.includes('ROLE_ADMIN');
  const [page, setPage] = useState(1);
  const [data, setData] = useState<Paginated<TestSession> | null>(null);
  const [fetching, setFetching] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);
  const [language, setLanguage] = useState('');
  const [location, setLocation] = useState('');
  const [options, setOptions] = useState<SessionFilterOptions>({ languages: [], locations: [] });

  const load = useCallback(async () => {
    setFetching(true);
    try {
      setData(await listSessions(page, 6, { language, location }));
      setError(null);
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setFetching(false);
    }
  }, [page, language, location]);

  useEffect(() => {
    if (user) load();
  }, [user, load]);

  useEffect(() => {
    if (user) getSessionFilters().then(setOptions).catch(() => undefined);
  }, [user]);

  const hasFilters = language !== '' || location !== '';

  function onFilter(setter: (value: string) => void, value: string) {
    setter(value);
    setPage(1);
  }

  function resetFilters() {
    setLanguage('');
    setLocation('');
    setPage(1);
  }

  async function onReserve(session: TestSession) {
    setMessage(null);
    setError(null);
    try {
      await reserveSession(session.id);
      setMessage(`Session ${session.language} du ${session.date} réservée.`);
      await load();
    } catch (e) {
      setError(errorMessage(e));
    }
  }

  if (loading || !user) return <p>Chargement…</p>;

  return (
    <section>
      <header className="page-header">
        <h1>Sessions disponibles</h1>
        <p className="page-subtitle">Choisissez une session de test de langue et réservez votre place.</p>
      </header>
      <form className="filters" aria-label="Filtrer les sessions" onSubmit={(e) => e.preventDefault()}>
        <label>
          Langue
          <select value={language} onChange={(e) => onFilter(setLanguage, e.target.value)}>
            <option value="">Toutes les langues</option>
            {options.languages.map((l) => (
              <option key={l} value={l}>
                {l}
              </option>
            ))}
          </select>
        </label>
        <label>
          Ville
          <select value={location} onChange={(e) => onFilter(setLocation, e.target.value)}>
            <option value="">Toutes les villes</option>
            {options.locations.map((l) => (
              <option key={l} value={l}>
                {l}
              </option>
            ))}
          </select>
        </label>
        {hasFilters && (
          <button type="button" className="link-button" onClick={resetFilters}>
            Réinitialiser
          </button>
        )}
        {data && (
          <p className="filters-count" role="status">
            {data.total} session{data.total > 1 ? 's' : ''}
          </p>
        )}
      </form>
      {message && <p role="status" className="alert alert-success">{message}</p>}
      {error && <p role="alert" className="alert alert-error">{error}</p>}
      {fetching && !data && (
        <div aria-busy="true" aria-label="Chargement">
          {[0, 1, 2].map((i) => (
            <div className="skeleton-row" key={i} />
          ))}
        </div>
      )}

      {data && data.items.length === 0 && (
        <p className="empty-state">
          {hasFilters ? 'Aucune session ne correspond à ces filtres.' : 'Aucune session disponible.'}
        </p>
      )}

      <ul className="rows">
        {data?.items.map((s) => {
          const level = s.remaining === 0 ? 'full' : s.remaining <= 3 ? 'low' : 'ok';
          const taken = s.places ? ((s.places - s.remaining) / s.places) * 100 : 0;
          return (
            <li className="session-row" key={s.id}>
              <div className="row-when">
                <strong>{s.date}</strong>
                <span>{s.time}</span>
              </div>
              <div className="row-main">
                <h3>{s.language}</h3>
                <p className="meta">{s.location}</p>
              </div>
              <div className="row-figure">
                <p className="figure price">{formatPrice(s.price)}</p>
                <p className="figure">
                  <b>{s.remaining}</b> / {s.places} places
                </p>
                <div
                  className={`meter meter-${level}`}
                  role="progressbar"
                  aria-label="Places occupées"
                  aria-valuemin={0}
                  aria-valuemax={s.places}
                  aria-valuenow={s.places - s.remaining}
                >
                  <span style={{ width: `${taken}%` }} />
                </div>
                {s.booked ? (
                  <span className="tag tag-neutral">Réservée</span>
                ) : level === 'full' ? (
                  <span className="tag tag-full">Complet</span>
                ) : level === 'low' ? (
                  <span className="tag tag-low">Dernières places</span>
                ) : null}
              </div>
              {!isAdmin && (
                <div className="row-action">
                  <button className="btn" disabled={s.booked || s.remaining === 0} onClick={() => onReserve(s)}>
                    {s.booked ? 'Déjà réservée' : s.remaining === 0 ? 'Complet' : 'Réserver'}
                  </button>
                </div>
              )}
            </li>
          );
        })}
      </ul>

      {data && <Pagination page={data.page} pages={data.pages} onChange={setPage} />}
    </section>
  );
}
