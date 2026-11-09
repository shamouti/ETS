'use client';

import { FormEvent, useEffect, useState } from 'react';
import { useRequireAuth } from '@/context/AuthContext';
import { errorMessage, updateMe } from '@/lib/api';

export default function AccountPage() {
  const { user, loading, setSession } = useRequireAuth();
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [message, setMessage] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);

  useEffect(() => {
    if (user) {
      setName(user.name);
      setEmail(user.email);
    }
  }, [user]);

  if (loading || !user) return <p>Chargement…</p>;

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setMessage(null);
    setError(null);
    try {
      const { token, user: updated } = await updateMe(name.trim(), email.trim().toLowerCase());
      setSession(token, updated);
      setMessage('Vos informations ont été mises à jour.');
    } catch (e) {
      setError(errorMessage(e));
    }
  }

  return (
    <form className="panel form" onSubmit={onSubmit}>
      <h1>Mon compte</h1>
      <label>
        Nom
        <input value={name} onChange={(e) => setName(e.target.value)} autoComplete="name" required />
      </label>
      <label>
        Email
        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" required />
      </label>
      {message && <p role="status" className="alert alert-success">{message}</p>}
      {error && <p role="alert" className="alert alert-error">{error}</p>}
      <button className="btn btn-block" type="submit">Enregistrer</button>
    </form>
  );
}
