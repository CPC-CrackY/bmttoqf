import { Injectable } from '@angular/core';
import { pki, util, random, cipher } from "node-forge";
import { HttpClient } from '@angular/common/http';
import { environment } from '../../../environments/environment';
import { firstValueFrom } from 'rxjs';

interface KeyPair {
  publicKey: any,
  privateKey: any
}

@Injectable({
  providedIn: 'root'
})
export class EncryptionService {

  private sharedKey: string = '';
  private decryptedSharedKey: string = '';
  private handshakePending: boolean = false;
  private intervals: any[] = [];

  private keypair: KeyPair = {
    publicKey: '',
    privateKey: ''
  }

  constructor(private http: HttpClient) { }


  setDecryptedSharedKey(decryptedSharedKey: string): void {
    this.decryptedSharedKey = decryptedSharedKey;
    localStorage.setItem('yadsk', btoa(this.decryptedSharedKey));
  }
  deleteDecryptedSharedKey(): void {
    this.decryptedSharedKey = '';
    localStorage.removeItem('yadsk');
  }

  async waitForDecryptedSharedKey(subject: string) {
    return new Promise(resolve => {
      const interval = setInterval(() => {
        if (this.decryptedSharedKey !== '') {
          this.handshakePending = false;
          clearInterval(interval);
          setTimeout(() => { this.clearAllIntervals() }, 1000);
          resolve(subject);
        }
      }, 100);
      this.intervals.push(interval);
    });
  }

  clearAllIntervals() {
    this.intervals.forEach((interval: any) => {
      if (interval) clearInterval(interval);
    });
    this.intervals = [];
  }
  /**
   * Create and share a public key to the API server
   * @param none
   * @returns none
   */
  async handshake(subject: string) {

    if (this.decryptedSharedKey !== '') return;
    if (sessionStorage.getItem('dnc') !== null || window.location.hostname.includes('localhost')) return;

    if (this.handshakePending) {
      await this.waitForDecryptedSharedKey(subject);
      if (this.decryptedSharedKey !== '') return;
    }
    this.handshakePending = true;

    // generate public/private key pair
    await pki.rsa.generateKeyPair({ bits: 2048, workers: 2 }, (err: any, k: any) => {
      this.keypair = k;
    });
    // convert public key to PEM format
    const publicKey = pki.publicKeyToPem(this.keypair.publicKey);
    try {
      // send the PEM formated public key to server and
      // save the encrypted shared key and handshake id
      const result: any = await firstValueFrom(this.http.post(`${environment.api_url}`,
        {
          "subject": 'handshake',
          "publicKey": publicKey,
        }));
      this.sharedKey = util.decode64(result.encryptedKey);
      try {
        this.setDecryptedSharedKey(this.keypair.privateKey.decrypt(this.sharedKey));
      } catch (error) {
        throw error;
      }
    } catch (error) {
      throw error;
    }
  }

  sleep = (ms: number) => {
    return new Promise(resolve => setTimeout(resolve, ms))
  }

  /**
   * Encrypt an object
   * @param object an object containing all the request parameters
   * @returns the encrypted string that will be decrypted server-side
   */
  async encrypt(object: any): Promise<string> {
    let ret: any;
    const subject = object.subject;
    await this.handshake(subject);
    // generate a random 16 byte IV
    const iv = random.getBytesSync(16);
    try {
      // encrypt the data using the random IV and the shared key
      const encryption = cipher.createCipher('AES-CBC', this.decryptedSharedKey);
      encryption.start({ iv: iv });
      encryption.update(util.createBuffer(JSON.stringify(object), 'utf8'));
      encryption.finish();

      ret = util.encode64(iv + encryption.output.data);
      this.setDecryptedSharedKey(this.decryptedSharedKey); // force store in localStorage
    } catch (error) {
      this.deleteDecryptedSharedKey();
      window.location.reload();
    }
    return ret;
  }

}
