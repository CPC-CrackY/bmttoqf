import { Component } from '@angular/core';
import { PermissionsService } from '../../services/permissions.service';

@Component({
  selector: 'app-core-logout',
  template: ''
})
export class CoreLogoutComponent {

  constructor(private permissionsService: PermissionsService) {
    this.permissionsService.logout();
  }

}
