import type { Metadata } from 'next';
import { GeistMono } from 'geist/font/mono';
import { GeistSans } from 'geist/font/sans';
import { ReactNode } from 'react';
import { AuthProvider } from '@/context/AuthContext';
import Navbar from '@/components/Navbar';
import './globals.css';

export const metadata: Metadata = {
  title: 'ETS - Réservation de tests de langues',
};

export default function RootLayout({ children }: { children: ReactNode }) {
  return (
    <html lang="fr" className={`${GeistSans.variable} ${GeistMono.variable}`}>
      <body>
        <AuthProvider>
          <a href="#main" className="skip-link">
            Aller au contenu
          </a>
          <Navbar />
          <main id="main" className="container">
            {children}
          </main>
        </AuthProvider>
      </body>
    </html>
  );
}
