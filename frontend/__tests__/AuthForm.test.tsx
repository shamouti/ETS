import { render, screen, waitFor } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import AuthForm from '@/components/AuthForm';

const push = jest.fn();
const login = jest.fn();
const register = jest.fn();

jest.mock('next/navigation', () => ({ useRouter: () => ({ push }) }));
jest.mock('@/context/AuthContext', () => ({ useAuth: () => ({ login, register }) }));

describe('AuthForm', () => {
  beforeEach(() => jest.clearAllMocks());

  it('connecte l’utilisateur puis redirige vers les réservations', async () => {
    login.mockResolvedValue(undefined);
    render(<AuthForm />);

    await userEvent.type(screen.getByLabelText('Email'), 'User@Example.com');
    await userEvent.type(screen.getByLabelText('Mot de passe'), 'Password123');
    await userEvent.click(screen.getByRole('button', { name: 'Se connecter' }));

    await waitFor(() => expect(login).toHaveBeenCalledWith('user@example.com', 'Password123'));
    expect(push).toHaveBeenCalledWith('/reservations');
  });

  it('affiche une erreur si la connexion échoue', async () => {
    login.mockRejectedValue(new Error('boom'));
    render(<AuthForm />);

    await userEvent.type(screen.getByLabelText('Email'), 'user@example.com');
    await userEvent.type(screen.getByLabelText('Mot de passe'), 'bad');
    await userEvent.click(screen.getByRole('button', { name: 'Se connecter' }));

    expect(await screen.findByRole('alert')).toBeInTheDocument();
    expect(push).not.toHaveBeenCalled();
  });

  it('bascule vers le formulaire d’inscription', async () => {
    render(<AuthForm />);
    await userEvent.click(screen.getByText(/Inscrivez-vous/));
    expect(screen.getByLabelText('Nom')).toBeInTheDocument();
  });
});
