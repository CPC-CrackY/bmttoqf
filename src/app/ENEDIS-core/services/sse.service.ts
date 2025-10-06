import { Injectable, NgZone } from '@angular/core';
import { environment } from '../../../environments/environment';
import { BehaviorSubject, Observable } from 'rxjs';
import { ApiAzurService } from './api-azur.service';

@Injectable({
  providedIn: 'root'
})
export class SseService {
  affaires: any;
  evs!: EventSource;
  private subscribed: any = [];
  private sources: any = [];
  private subj = new BehaviorSubject([]);

  constructor(private apiAzurService: ApiAzurService, private zone: NgZone) { }

  subscribeToCoreSSE(
    url: string,
    type: string,
    listener: (this: EventSource, event: MessageEvent<any>) => any,
    options: boolean | AddEventListenerOptions | undefined = false) {
    const uniqueName = this.getUniqueName(url, type);
    if (!this.subscribed[uniqueName]) {
      this.subscribed[uniqueName] = true;
      this.sources[uniqueName] = new EventSource(url, { withCredentials: true });
      this.sources[uniqueName].onmessage = function (event: any) {
      }
      this.sources[uniqueName].addEventListener(type, listener, options);
      this.sources[uniqueName].onerror = (event: any) => {
      }
      this.sources[uniqueName].onopen = (event: any) => {
      };
    }
  }

  updateObjectWithKeys<T extends Record<string, any>>(
    objectToModify: T[],
    valuesToInsertOrUpdate: Partial<T>,
    primaryKeys: keyof T | (keyof T)[]
  ): void {
    const keys = Array.isArray(primaryKeys) ? primaryKeys : [primaryKeys];
    objectToModify.forEach((item) => {
      const shouldUpdate = keys.every((key) => item[key] === valuesToInsertOrUpdate[key]);
      if (shouldUpdate) {
        Object.assign(item, valuesToInsertOrUpdate);
      }
    });
    objectToModify = [...objectToModify];
  }

  getAndKeepUpdated(eventName: string, variable: Record<string, any>[], url: string, primaryKeys: keyof Record<string, any> | (keyof Record<string, any>)[]) {
    url += -1 === url.indexOf('?') ? '?' : '&';
    let date = new Date();
    url += 'isoTime=' + date.toISOString();
    this.get(variable, url).then(() => {
      this.keepUpdated(eventName, variable, url, primaryKeys)
    });
  }

  async get(variable: Record<string, any>[], url: string) {
    url += '&firstSseCall=true';
    await this.apiAzurService.get('', url).then((data) => {
      // clear the original array
      variable.length = 0;
      // push the new items into the original array
      data.forEach((item: Record<string, any>) => variable.push(item));
    })
  }

  keepUpdated(eventName: string, variable: Record<string, any>[], url: string, primaryKeys: keyof Record<string, any> | (keyof Record<string, any>)[]) {
    const uniqueName = this.getUniqueName(url, eventName);
    if (!this.subscribed[uniqueName]) {
      this.subscribed[uniqueName] = true;
      this.sources[uniqueName] = new EventSource(url);
      this.sources[uniqueName].addEventListener(eventName, (event: any) => {
        const valuesToInsertOrUpdate = JSON.parse(event.data);
        this.updateObjectWithKeys(variable, valuesToInsertOrUpdate, primaryKeys);
      });
      this.sources[uniqueName].onmessage = (event: any) => {
        const valuesToInsertOrUpdate = JSON.parse(event.data);
        this.updateObjectWithKeys(variable, valuesToInsertOrUpdate, primaryKeys);
      }
      this.sources[uniqueName].onerror = (event: any) => {
      }
      this.sources[uniqueName].onopen = (event: any) => {
      };
    }
  }

  unsubscribeToCoreSSE(eventName: string, url: string) {
    this.sources[this.getUniqueName(url, eventName)].close();
  }

  getUniqueName(url: string, eventName: string) {
    return url + '/' + eventName;
  }

  /**
   * getServerSentEvent() returns un observable
   * usage :
   * receivedEvent : { data: string, type: string, lastEventId: string, timeStamp: number }
   * this._sseService.getServerSentEvent(url).subscribe((event)=>{
   *     // will contain event.id, event.event, event.data
   *     this.receivedEvent = JSON.parse(event.data);
   * })
   * @param url srting
   * @returns Observable
   */
  getServerSentEvent(url: string) {
    return new Observable(observer => {
      const eventSource = new EventSource(url);

      eventSource.onmessage = event => {
        this.zone.run(() => {
          observer.next(event);
        })
      }

      eventSource.onerror = error => {
        this.zone.run(() => {
          observer.error(error);
        })
      }
    })
  }

}
