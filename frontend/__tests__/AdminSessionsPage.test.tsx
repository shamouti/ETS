import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import AdminSessionsPage from '@/app/admin/sessions/page';
import * as api from '@/lib/api';

const replace = jest.fn();
let mockUser: { id: string; name: string; email: string; roles: string[] };

jest.mock('next/navigation', () => ({ useRouter: () => ({ replace, push: jest.fn() }) }));
jest.mock('@/context/AuthContext', () => ({
  useRequireAuth: () => ({ user: mockUser, loading: false }),
}));
jest.mock('@/lib/api', () => ({
  ...jest.requireActual('@/lib/api'),
  listSessions: jest.fn(),
  createSession: jest.fn(),
  deleteSession: jest.fn(),
  updateSession: jest.fn(),
}));

const admin = { id: '1', name: 'Admin', email: 'a@e.com', roles: ['ROLE_ADMIN', 'ROLE_USER'] };
const sessions = {
  items: [{ id: 'a', language: 'Anglais', date: '2030-01-01', time: '09:00', location: 'Paris', price: 95, places: 10, remaining: 4 }],
  page: 1,
  limit: 8,
  total: 1,
  pages: 1,
};

describe('AdminSessionsPage', () => {
  beforeEach(() => {
    jest.clearAllMocks();
    mockUser = admin;
    (api.listSessions as jest.Mock).mockResolvedValue(sessions);
  });

  it('redirige un utilisateur non admin', async () => {
    mockUser = { ...admin, roles: ['ROLE_USER'] };
    render(<AdminSessionsPage />);
    await waitFor(() => expect(replace).toHaveBeenCalledWith('/sessions'));
    expect(api.listSessions).not.toHaveBeenCalled();
  });

  it('crée une session', async () => {
    (api.createSession as jest.Mock).mockResolvedValue({});
    const { container } = render(<AdminSessionsPage />);
    await screen.findByText('Anglais');

    await userEvent.type(screen.getByLabelText('Langue'), 'Italien');
    await userEvent.type(screen.getByLabelText('Lieu'), 'Rome');
    await userEvent.clear(screen.getByLabelText('Prix (MAD)'));
    await userEvent.type(screen.getByLabelText('Prix (MAD)'), '895.5');
    const [date, time] = [container.querySelector('input[type=date]')!, container.querySelector('input[type=time]')!];
    await userEvent.type(date, '2030-02-01');
    await userEvent.type(time, '10:00');
    await userEvent.click(screen.getByRole('button', { name: 'Créer' }));

    await waitFor(() =>
      expect(api.createSession).toHaveBeenCalledWith({
        language: 'Italien',
        date: '2030-02-01',
        time: '10:00',
        location: 'Rome',
        price: 895.5,
        places: 10,
      }),
    );
  });

  it('supprime une session après confirmation', async () => {
    jest.spyOn(window, 'confirm').mockReturnValue(true);
    (api.deleteSession as jest.Mock).mockResolvedValue(undefined);
    render(<AdminSessionsPage />);

    await userEvent.click(await screen.findByRole('button', { name: 'Supprimer' }));

    await waitFor(() => expect(api.deleteSession).toHaveBeenCalledWith('a'));
  });

  it('pré-remplit le formulaire pour modifier', async () => {
    render(<AdminSessionsPage />);
    window.scrollTo = jest.fn();

    await userEvent.click(await screen.findByRole('button', { name: 'Modifier' }));

    expect(screen.getByLabelText('Langue')).toHaveValue('Anglais');
    expect(screen.getByLabelText('Prix (MAD)')).toHaveValue(95);
    expect(screen.getByRole('button', { name: 'Enregistrer' })).toBeInTheDocument();
  });
});
