import { Injectable } from '@angular/core';
import { CanActivate, Router } from '@angular/router';
import { PermissionsService } from './permissions.service';

@Injectable({
  providedIn: 'root'
})
export class CanActivateService implements CanActivate {

  constructor(protected router: Router, protected permissionsService: PermissionsService) { }

  canActivate(route: any): Promise<boolean> | boolean {
    return this.hasRequiredPermission(route.data['auth']);
  }

  protected hasRequiredPermission(authGroup: string[]): Promise<boolean> | boolean {
    // If user’s permissions already retrieved from the API
    if (this.permissionsService.myGrants) {
      if (authGroup) {
        return this.permissionsService.hasPermissionWithAlert(authGroup);
      } else {
        return this.permissionsService.hasPermissionWithAlert('thisIsNotValid');
      }
    } else {
      // Otherwise, must request permissions from the API first
      const promise = new Promise<boolean>((resolve) => {
        this.permissionsService.initializeGrants()
          .then(() => {
            if (authGroup) {
              resolve(this.permissionsService.hasPermissionWithAlert(authGroup));
            } else {
              resolve(this.permissionsService.hasPermissionWithAlert('thisIsNotValid'));
            }
          }).catch(() => {
            resolve(false);
          });
      });
      return promise;
    }
  }
}