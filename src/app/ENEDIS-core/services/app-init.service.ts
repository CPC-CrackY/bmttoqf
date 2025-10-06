import { Injectable } from '@angular/core';
import { PermissionsService } from './permissions.service';
import { EncryptionService } from './encryption.service';

@Injectable({
    providedIn: 'root'
})
export class AppInitService {

    constructor(
        readonly permissionService: PermissionsService,
        readonly encryptionService: EncryptionService
    ) { }

    loadAppState(): Promise<void> {
        return new Promise((resolve) => {
            localStorage.removeItem('jsVersion');
            const token = localStorage.getItem('token');
            if (token) {
                this.permissionService.setAccessToken(atob(token));
                console.info('Token set :', atob(token));
            }
            const decryptedSharedKey = localStorage.getItem('yadsk');
            if (decryptedSharedKey) {
                this.encryptionService.setDecryptedSharedKey(atob(decryptedSharedKey));
                console.info('Decrypted Shared Key set :', atob(decryptedSharedKey));
            }
            resolve();
        });
    }
}