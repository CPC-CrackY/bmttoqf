import { Directive, ElementRef, OnInit, Input } from '@angular/core';
import { PermissionsService } from '../services/permissions.service';

@Directive({
  selector: '[requiredPermissionToDisplay]'
})
export class PermissionToDisplayDirective implements OnInit {

  @Input('requiredPermissionToDisplay') requiredPermissionToDisplay: any;

  constructor(private el: ElementRef, private permissionsService: PermissionsService) { }

  ngOnInit(): void {
    if (this.requiredPermissionToDisplay) {
      if (!this.permissionsService.hasPermissionWithAlert(this.requiredPermissionToDisplay)) {
        this.el.nativeElement.style.display = 'none';
      } else {
        this.el.nativeElement.style.display = 'inherit';
      }
    }
  }

}
