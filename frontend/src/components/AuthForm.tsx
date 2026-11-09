'use client';

import { FormEvent, useState } from 'react';
import { useRouter } from 'next/navigation';
import { useAuth } from '@/context/AuthContext';
import { errorMessage } from '@/lib/api';

export default function AuthForm() {
  const { login, register } = useAuth();
  const router = useRouter();
  const [mode, setMode] = useState<'login' | 'register'>('login');
  const [name, setName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [submitting, setSubmitting] = useState(false);

  const isLogin = mode === 'login';

  async function onSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);
    setSubmitting(true);
    try {
      const cleanEmail = email.trim().toLowerCase();
      if (isLogin) {
        await login(cleanEmail, password);
      } else {
        await register(name.trim(), cleanEmail, password);
      }
      router.push('/reservations');
    } catch (e) {
      setError(errorMessage(e));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <>
    <div className="auth-brand">
      <strong className="navbar-brand">ETS Language Tests</strong>
      <p>Réservez votre session de test de langue en quelques clics.</p>
    </div>
    <form className="panel form" onSubmit={onSubmit}>
      <h1>{isLogin ? 'Connexion' : 'Créer un compte'}</h1>

      {!isLogin && (
        <label>
          Nom
          <input value={name} onChange={(e) => setName(e.target.value)} autoComplete="name" required />
        </label>
      )}
      <label>
        Email
        <input type="email" value={email} onChange={(e) => setEmail(e.target.value)} autoComplete="email" required />
      </label>
      <label>
        Mot de passe
        <input
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
          minLength={isLogin ? undefined : 8}
          autoComplete={isLogin ? 'current-password' : 'new-password'}
          required
        />
      </label>

      {error && <p role="alert" className="alert alert-error">{error}</p>}

      <button className="btn btn-block" type="submit" disabled={submitting}>
        {isLogin ? 'Se connecter' : "S'inscrire"}
      </button>
      <button
        type="button"
        className="link-button"
        onClick={() => {
          setMode(isLogin ? 'register' : 'login');
          setError(null);
        }}
      >
        {isLogin ? 'Pas de compte ? Inscrivez-vous' : 'Déjà un compte ? Connectez-vous'}
      </button>
    </form>
    </>
  );
}
