import { Directive, ElementRef, OnInit, Input } from '@angular/core';
import { PermissionsService } from '../services/permissions.service';

@Directive({
  selector: '[requiredPermissionToHideSilently]'
})
export class PermissionToHideSilentlyDirective implements OnInit {

  @Input('requiredPermissionToHideSilently') requiredPermissionToHideSilently: any;

  constructor(private el: ElementRef, private permissionsService: PermissionsService) { }

  ngOnInit(): void {
    if (this.requiredPermissionToHideSilently) {
      if (!this.permissionsService.hasPermission(this.requiredPermissionToHideSilently)) {
        //   this.el.nativeElement.style.display = 'inherit';
      } else {
        this.el.nativeElement.style.display = 'none';
      }
    }
  }

}
