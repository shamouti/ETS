import axios, { AxiosError } from 'axios';

export interface User {
  id: string;
  name: string;
  email: string;
  roles: string[];
}

export interface TestSession {
  id: string;
  language: string;
  date: string;
  time: string;
  location: string;
  price: number;
  places: number;
  remaining: number;
  booked?: boolean;
}

const priceFormatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'MAD' });

export const formatPrice = (price: number | undefined): string =>
  price ? priceFormatter.format(price) : 'Gratuit';

export interface Reservation {
  id: string;
  createdAt: string;
  session: TestSession;
}

export interface Paginated<T> {
  items: T[];
  page: number;
  limit: number;
  total: number;
  pages: number;
}

interface AuthResponse {
  token: string;
  user: User;
}

const TOKEN_KEY = 'ets_token';

export const tokenStore = {
  get: (): string | null => (typeof window === 'undefined' ? null : window.localStorage.getItem(TOKEN_KEY)),
  set: (token: string) => window.localStorage.setItem(TOKEN_KEY, token),
  clear: () => window.localStorage.removeItem(TOKEN_KEY),
};

export const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_URL ?? 'http://localhost:8001/api',
});

api.interceptors.request.use((config) => {
  const token = tokenStore.get();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export function errorMessage(error: unknown): string {
  if (axios.isAxiosError(error)) {
    const data = (error as AxiosError<{ error?: string; message?: string; violations?: Record<string, string> }>).response?.data;
    if (data?.violations) {
      return Object.entries(data.violations)
        .map(([field, message]) => `${field}: ${message}`)
        .join(' / ');
    }
    return data?.error ?? data?.message ?? 'Erreur réseau, veuillez réessayer.';
  }
  return 'Une erreur inattendue est survenue.';
}

export const login = async (email: string, password: string): Promise<string> =>
  (await api.post<{ token: string }>('/login', { email, password })).data.token;

export const register = async (name: string, email: string, password: string): Promise<AuthResponse> =>
  (await api.post<AuthResponse>('/register', { name, email, password })).data;

export const getMe = async (): Promise<User> => (await api.get<User>('/me')).data;

export const updateMe = async (name: string, email: string): Promise<AuthResponse> =>
  (await api.put<AuthResponse>('/me', { name, email })).data;

export interface SessionFilters {
  language?: string;
  location?: string;
}

export interface SessionFilterOptions {
  languages: string[];
  locations: string[];
}

export const listSessions = async (page: number, limit = 6, filters: SessionFilters = {}): Promise<Paginated<TestSession>> =>
  (
    await api.get<Paginated<TestSession>>('/sessions', {
      params: { page, limit, language: filters.language || undefined, location: filters.location || undefined },
    })
  ).data;

export const getSessionFilters = async (): Promise<SessionFilterOptions> =>
  (await api.get<SessionFilterOptions>('/sessions/filters')).data;

export interface SessionInput {
  language: string;
  date: string;
  time: string;
  location: string;
  price: number;
  places: number;
}

export const createSession = async (input: SessionInput): Promise<TestSession> =>
  (await api.post<TestSession>('/sessions', input)).data;

export const updateSession = async (id: string, input: SessionInput): Promise<TestSession> =>
  (await api.put<TestSession>(`/sessions/${id}`, input)).data;

export const deleteSession = async (id: string): Promise<void> => {
  await api.delete(`/sessions/${id}`);
};

export const reserveSession = async (sessionId: string): Promise<Reservation> =>
  (await api.post<Reservation>(`/sessions/${sessionId}/reservations`)).data;

export const listReservations = async (): Promise<Reservation[]> =>
  (await api.get<Reservation[]>('/reservations')).data;

export const cancelReservation = async (reservationId: string): Promise<void> => {
  await api.delete(`/reservations/${reservationId}`);
};
