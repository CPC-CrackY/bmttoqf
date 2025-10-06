import { Directive, ElementRef, OnInit, Input } from '@angular/core';
import { PermissionsService } from '../services/permissions.service';

@Directive({
  selector: '[requiredPermissionToDisplaySilently]'
})
export class PermissionToDisplaySilentlyDirective implements OnInit {

  @Input('requiredPermissionToDisplaySilently') requiredPermissionToDisplaySilently: any;

  constructor(private el: ElementRef, private permissionsService: PermissionsService) { }

  ngOnInit(): void {
    if (this.requiredPermissionToDisplaySilently) {
      const save = this.el.nativeElement.style.display;
      this.el.nativeElement.style.display = 'none';
      if (!this.permissionsService.hasPermission(this.requiredPermissionToDisplaySilently)) {
        this.el.nativeElement.style.display = 'none';
      } else {
        this.el.nativeElement.style.display = save;
      }
    }
  }

}
