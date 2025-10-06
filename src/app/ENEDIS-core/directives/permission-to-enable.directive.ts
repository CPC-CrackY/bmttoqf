import { Directive, ElementRef, OnInit, Input } from '@angular/core';
import { PermissionsService } from '../services/permissions.service';

@Directive({
  selector: '[requiredPermissionToEnable]'
})
export class PermissionToEnableDirective implements OnInit {

  @Input('requiredPermissionToEnable') requiredPermissionToEnable: any;

  constructor(private el: ElementRef, private permissionsService: PermissionsService) { }

  ngOnInit() {
    if (!this.permissionsService.hasPermission(this.requiredPermissionToEnable)) {
      this.el.nativeElement.disabled = true;
      this.el.nativeElement.title = 'Vous n\'avez pas les droits nécessaires à cette action';
    }
  }

}
