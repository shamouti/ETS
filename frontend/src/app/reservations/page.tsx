'use client';

import Link from 'next/link';
import { useCallback, useEffect, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useRequireAuth } from '@/context/AuthContext';
import { cancelReservation, errorMessage, formatPrice, listReservations, Reservation } from '@/lib/api';

export default function ReservationsPage() {
  const { user, loading } = useRequireAuth();
  const router = useRouter();
  const isAdmin = !!user?.roles.includes('ROLE_ADMIN');
  const [reservations, setReservations] = useState<Reservation[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(async () => {
    try {
      setReservations(await listReservations());
      setError(null);
    } catch (e) {
      setError(errorMessage(e));
    }
  }, []);

  useEffect(() => {
    if (isAdmin) router.replace('/admin/sessions');
    else if (user) load();
  }, [user, isAdmin, router, load]);

  async function onCancel(reservation: Reservation) {
    if (!window.confirm('Annuler cette réservation ?')) return;
    try {
      await cancelReservation(reservation.id);
      await load();
    } catch (e) {
      setError(errorMessage(e));
    }
  }

  if (loading || !user) return <p>Chargement…</p>;

  return (
    <section>
      <header className="page-header">
        <h1>Mes réservations</h1>
        <p className="page-subtitle">Retrouvez et gérez vos sessions réservées.</p>
      </header>
      {error && <p role="alert" className="alert alert-error">{error}</p>}
      {reservations && reservations.length === 0 && (
        <p className="empty-state">
          Aucune réservation. <Link href="/sessions">Voir les sessions disponibles</Link>
        </p>
      )}
      <ul className="rows">
        {reservations?.map((r) => (
          <li className="session-row" key={r.id}>
            <div className="row-when">
              <strong>{r.session.date}</strong>
              <span>{r.session.time}</span>
            </div>
            <div className="row-main">
              <h3>{r.session.language}</h3>
              <p className="meta">{r.session.location}</p>
            </div>
            <div className="row-figure">
              <p className="figure price">{formatPrice(r.session.price)}</p>
              <span className="tag tag-ok">Confirmée</span>
            </div>
            <div className="row-action">
              <button className="btn btn-danger" onClick={() => onCancel(r)}>
                Annuler la réservation
              </button>
            </div>
          </li>
        ))}
      </ul>
    </section>
  );
}
