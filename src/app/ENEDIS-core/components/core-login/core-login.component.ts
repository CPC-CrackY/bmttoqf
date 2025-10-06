import { Component } from '@angular/core';
import { PermissionsService } from '../../services/permissions.service';

@Component({
  selector: 'app-core-login',
  template: ''
})
export class CoreLoginComponent {

  constructor(private permissionsService: PermissionsService) {
    this.permissionsService.disconnectUser().then(() => {
      this.permissionsService.logout(false).then(() => { this.permissionsService.login() });
    });
  }

}
