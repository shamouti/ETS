'use client';

import { FormEvent, useCallback, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import Pagination from '@/components/Pagination';
import { useRequireAuth } from '@/context/AuthContext';
import {
  createSession,
  deleteSession,
  errorMessage,
  formatPrice,
  listSessions,
  Paginated,
  SessionInput,
  TestSession,
  updateSession,
} from '@/lib/api';

const EMPTY: SessionInput = { language: '', date: '', time: '', location: '', price: 0, places: 10 };

export default function AdminSessionsPage() {
  const { user, loading } = useRequireAuth();
  const router = useRouter();
  const isAdmin = !!user?.roles.includes('ROLE_ADMIN');

  const [page, setPage] = useState(1);
  const [data, setData] = useState<Paginated<TestSession> | null>(null);
  const [form, setForm] = useState<SessionInput>(EMPTY);
  const [editingId, setEditingId] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [message, setMessage] = useState<string | null>(null);

  useEffect(() => {
    if (!loading && user && !isAdmin) router.replace('/sessions');
  }, [loading, user, isAdmin, router]);

  const load = useCallback(async (targetPage: number) => {
    try {
      const result = await listSessions(targetPage, 8);
      if (result.items.length === 0 && targetPage > 1) {
        setPage(targetPage - 1);
        return;
      }
      setData(result);
    } catch (e) {
      setError(errorMessage(e));
    }
  }, []);

  useEffect(() => {
    if (isAdmin) load(page);
  }, [isAdmin, page, load]);

  function reset() {
    setForm(EMPTY);
    setEditingId(null);
  }

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setMessage(null);
    try {
      if (editingId) {
        await updateSession(editingId, form);
        setMessage('Session modifiée.');
      } else {
        await createSession(form);
        setMessage('Session créée.');
      }
      reset();
      await load(page);
    } catch (e) {
      setError(errorMessage(e));
    }
  }

  function onEdit(session: TestSession) {
    setEditingId(session.id);
    setForm({
      language: session.language,
      date: session.date,
      time: session.time,
      location: session.location,
      price: session.price ?? 0,
      places: session.places,
    });
    setError(null);
    setMessage(null);
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  async function onDelete(session: TestSession) {
    const booked = session.places - session.remaining;
    const warning = booked > 0 ? ` ${booked} réservation(s) seront aussi supprimées.` : '';
    if (!window.confirm(`Supprimer la session ${session.language} du ${session.date} ?${warning}`)) return;
    setError(null);
    setMessage(null);
    try {
      await deleteSession(session.id);
      if (editingId === session.id) reset();
      setMessage('Session supprimée.');
      await load(page);
    } catch (e) {
      setError(errorMessage(e));
    }
  }

  if (loading || !isAdmin) return <p>Chargement…</p>;

  const set = <K extends keyof SessionInput>(key: K, value: SessionInput[K]) => setForm((f) => ({ ...f, [key]: value }));

  return (
    <section>
      <header className="page-header">
        <h1>Gestion des sessions</h1>
        <p className="page-subtitle">Créez, modifiez et supprimez les sessions de test.</p>
      </header>

      <form className="panel form admin-form" onSubmit={onSubmit}>
        <h2>{editingId ? 'Modifier la session' : 'Nouvelle session'}</h2>
        <label>
          Langue
          <input value={form.language} onChange={(e) => set('language', e.target.value)} required />
        </label>
        <label>
          Date
          <input type="date" value={form.date} onChange={(e) => set('date', e.target.value)} required />
        </label>
        <label>
          Heure
          <input type="time" value={form.time} onChange={(e) => set('time', e.target.value)} required />
        </label>
        <label>
          Lieu
          <input value={form.location} onChange={(e) => set('location', e.target.value)} required />
        </label>
        <label>
          Prix (MAD)
          <input
            type="number"
            min={0}
            max={100000}
            step="0.01"
            value={form.price}
            onChange={(e) => set('price', Number(e.target.value))}
            required
          />
        </label>
        <label>
          Nombre de places
          <input
            type="number"
            min={1}
            value={form.places}
            onChange={(e) => set('places', Number(e.target.value))}
            required
          />
        </label>
        <div className="form-actions">
          <button className="btn" type="submit">
            {editingId ? 'Enregistrer' : 'Créer'}
          </button>
          {editingId && (
            <button className="btn btn-secondary" type="button" onClick={reset}>
              Annuler
            </button>
          )}
        </div>
      </form>

      {message && <p role="status" className="alert alert-success">{message}</p>}
      {error && <p role="alert" className="alert alert-error">{error}</p>}

      <div className="table-wrapper panel">
        <table>
          <thead>
            <tr>
              <th>Langue</th>
              <th>Date</th>
              <th>Heure</th>
              <th>Lieu</th>
              <th>Prix</th>
              <th>Places (restantes / total)</th>
              <th />
            </tr>
          </thead>
          <tbody>
            {data?.items.map((s) => (
              <tr key={s.id}>
                <td>{s.language}</td>
                <td>{s.date}</td>
                <td>{s.time}</td>
                <td>{s.location}</td>
                <td>{formatPrice(s.price)}</td>
                <td>
                  <span className={`tag ${s.remaining === 0 ? 'tag-full' : s.remaining <= 3 ? 'tag-low' : 'tag-ok'}`}>
                    {s.remaining} / {s.places}
                  </span>
                </td>
                <td>
                  <div className="row-actions">
                    <button className="btn btn-secondary" onClick={() => onEdit(s)}>
                      Modifier
                    </button>
                    <button className="btn btn-danger" onClick={() => onDelete(s)}>
                      Supprimer
                    </button>
                  </div>
                </td>
              </tr>
            ))}
            {data && data.items.length === 0 && (
              <tr>
                <td colSpan={7} className="empty-state">Aucune session.</td>
              </tr>
            )}
          </tbody>
        </table>
      </div>

      {data && <Pagination page={data.page} pages={data.pages} onChange={setPage} />}
    </section>
  );
}
