'use client';

import Link from 'next/link';
import { usePathname, useRouter } from 'next/navigation';
import { useEffect, useState } from 'react';
import { useAuth } from '@/context/AuthContext';

export default function Navbar() {
  const { user, logout } = useAuth();
  const router = useRouter();
  const pathname = usePathname();
  const [open, setOpen] = useState(false);

  useEffect(() => {
    setOpen(false);
  }, [pathname]);

  useEffect(() => {
    if (!open) return;
    const onKey = (e: KeyboardEvent) => e.key === 'Escape' && setOpen(false);
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, [open]);

  if (!user) return null;

  const navLink = (href: string, label: string) => (
    <Link href={href} className="navbar-link" aria-current={pathname === href ? 'page' : undefined}>
      {label}
    </Link>
  );

  return (
    <nav className="navbar" aria-label="Navigation principale">
      <strong className="navbar-brand">ETS Language Tests</strong>
      <button
        type="button"
        className="navbar-toggle"
        aria-expanded={open}
        aria-controls="navbar-links"
        onClick={() => setOpen((o) => !o)}
      >
        <span className="navbar-toggle-icon" aria-hidden="true" />
        <span className="sr-only">{open ? 'Fermer le menu' : 'Ouvrir le menu'}</span>
      </button>
      <div id="navbar-links" className={`navbar-links${open ? ' is-open' : ''}`}>
        {navLink('/sessions', 'Sessions')}
        {user.roles.includes('ROLE_ADMIN')
          ? navLink('/admin/sessions', 'Gestion des sessions')
          : navLink('/reservations', 'Mes réservations')}
        {navLink('/account', 'Mon compte')}
        <button
          className="btn btn-secondary"
          onClick={() => {
            logout();
            router.push('/login');
          }}
        >
          Déconnexion
        </button>
      </div>
    </nav>
  );
}
