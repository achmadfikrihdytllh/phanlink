export interface User {
  id: number;
  name: string;
  username: string;
  email: string;
  avatar: string | null;
  bio: string | null;
  phone_number: string | null;
  status: 'online' | 'offline';
  last_seen_at: string | null;
}

export interface AuthResponse {
  message: string;
  user: User;
  token: string;
}