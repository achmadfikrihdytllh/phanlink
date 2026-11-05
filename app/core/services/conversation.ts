import { Injectable } from '@angular/core';
import { HttpClient } from '@angular/common/http';
import { Observable } from 'rxjs';
import { environment } from '../../../environments/environment';
import { Conversation } from '../models/conversation';

@Injectable({
  providedIn: 'root',
})
export class ConversationService {
  private apiUrl = environment.apiUrl;

  constructor(private http: HttpClient) {}

  list(): Observable<{ conversations: Conversation[] }> {
    return this.http.get<{ conversations: Conversation[] }>(
      `${this.apiUrl}/conversations`
    );
  }

  getDetail(id: number): Observable<{ conversation: Conversation }> {
    return this.http.get<{ conversation: Conversation }>(
      `${this.apiUrl}/conversations/${id}`
    );
  }

  startPrivate(userId: number): Observable<{ conversation: Conversation }> {
    return this.http.post<{ conversation: Conversation }>(
      `${this.apiUrl}/conversations/private`,
      { user_id: userId }
    );
  }

  createGroup(data: {
    name: string;
    avatar?: string;
    participant_ids: number[];
  }): Observable<{ conversation: Conversation }> {
    return this.http.post<{ conversation: Conversation }>(
      `${this.apiUrl}/conversations/group`,
      data
    );
  }
}