import { Injectable } from '@angular/core';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import { environment } from '../../../environments/environment';

@Injectable({
  providedIn: 'root',
})
export class EchoService {
  private echo: Echo<any> | null = null;

  connect(): Echo<any> {
    if (this.echo) {
      return this.echo;
    }

    (window as any).Pusher = Pusher;

    const token = localStorage.getItem('token');
    const broadcastAuthUrl = environment.apiUrl.replace('/api', '') + '/broadcasting/auth';

    this.echo = new Echo({
      broadcaster: 'reverb',
      key: environment.reverbAppKey,
      wsHost: environment.reverbHost,
      wsPort: environment.reverbPort,
      wssPort: environment.reverbPort,
      forceTLS: environment.reverbScheme === 'https',
      enabledTransports: ['ws', 'wss'],
      authEndpoint: broadcastAuthUrl,
      auth: {
        headers: {
          Authorization: `Bearer ${token}`,
          Accept: 'application/json',
        },
      },
    });

    return this.echo;
  }

  listenToConversation(conversationId: number, callback: (data: any) => void): void {
    this.connect()
      .private(`conversation.${conversationId}`)
      .listen('.message.sent', callback);
  }

  leaveConversation(conversationId: number): void {
    this.echo?.leave(`conversation.${conversationId}`);
  }

  disconnect(): void {
    this.echo?.disconnect();
    this.echo = null;
  }
}