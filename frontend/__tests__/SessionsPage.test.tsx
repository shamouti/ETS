import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import SessionsPage from '@/app/sessions/page';
import * as api from '@/lib/api';

jest.mock('next/navigation', () => ({ useRouter: () => ({ replace: jest.fn(), push: jest.fn() }) }));
jest.mock('@/context/AuthContext', () => {
  const auth = { user: { id: '1', name: 'Test', email: 't@e.com', roles: [] }, loading: false };
  return { useRequireAuth: () => auth };
});
jest.mock('@/lib/api', () => ({
  ...jest.requireActual('@/lib/api'),
  listSessions: jest.fn(),
  getSessionFilters: jest.fn(),
  reserveSession: jest.fn(),
}));

const sessions = {
  items: [
    { id: 'a', language: 'Anglais', date: '2030-01-01', time: '09:00', location: 'Paris', places: 10, remaining: 4, booked: false },
    { id: 'b', language: 'Français', date: '2030-01-02', time: '10:00', location: 'Lyon', places: 5, remaining: 0, booked: false },
    { id: 'c', language: 'Italien', date: '2030-01-03', time: '11:00', location: 'Rome', places: 5, remaining: 2, booked: true },
  ],
  page: 1,
  limit: 6,
  total: 3,
  pages: 1,
};

describe('SessionsPage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    (api.listSessions as jest.Mock).mockResolvedValue(sessions);
    (api.getSessionFilters as jest.Mock).mockResolvedValue({
      languages: ['Anglais', 'Français', 'Italien'],
      locations: ['Lyon', 'Paris', 'Rome'],
    });
  });

  it('filtre par langue et par ville, puis réinitialise', async () => {
    render(<SessionsPage />);
    await screen.findByRole('heading', { name: 'Anglais' });
    await screen.findByRole('option', { name: 'Rome' });

    await userEvent.selectOptions(screen.getByLabelText('Langue'), 'Italien');
    await waitFor(() =>
      expect(api.listSessions).toHaveBeenLastCalledWith(1, 6, { language: 'Italien', location: '' }),
    );

    await userEvent.selectOptions(screen.getByLabelText('Ville'), 'Rome');
    await waitFor(() =>
      expect(api.listSessions).toHaveBeenLastCalledWith(1, 6, { language: 'Italien', location: 'Rome' }),
    );

    await userEvent.click(screen.getByRole('button', { name: 'Réinitialiser' }));
    await waitFor(() => expect(api.listSessions).toHaveBeenLastCalledWith(1, 6, { language: '', location: '' }));
    expect(screen.queryByRole('button', { name: 'Réinitialiser' })).not.toBeInTheDocument();
  });

  it('affiche un message quand aucun résultat ne correspond', async () => {
    (api.listSessions as jest.Mock).mockResolvedValue({ ...sessions, items: [], total: 0 });
    render(<SessionsPage />);
    await userEvent.selectOptions(await screen.findByLabelText('Langue'), 'Anglais');

    expect(await screen.findByText(/ne correspond/)).toBeInTheDocument();
  });

  it('affiche les sessions avec le bon état des boutons', async () => {
    render(<SessionsPage />);

    expect(await screen.findByRole('heading', { name: 'Anglais' })).toBeInTheDocument();
    expect(screen.getByRole('button', { name: 'Réserver' })).toBeEnabled();
    expect(screen.getByRole('button', { name: 'Complet' })).toBeDisabled();
    expect(screen.getByRole('button', { name: 'Déjà réservée' })).toBeDisabled();
  });

  it('réserve une session puis recharge la liste', async () => {
    (api.reserveSession as jest.Mock).mockResolvedValue({});
    render(<SessionsPage />);

    await userEvent.click(await screen.findByRole('button', { name: 'Réserver' }));

    await waitFor(() => expect(api.reserveSession).toHaveBeenCalledWith('a'));
    await waitFor(() => expect(api.listSessions).toHaveBeenCalledTimes(2));
    expect(await screen.findByText(/réservée\./)).toBeInTheDocument();
  });
});
