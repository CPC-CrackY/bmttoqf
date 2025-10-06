import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';
import { PermissionToDisplayDirective } from './permission-to-display.directive';
import { PermissionToEnableDirective } from './permission-to-enable.directive';
import { PermissionToDisplaySilentlyDirective } from './permission-to-display-silently.directive';
import { PermissionToHideSilentlyDirective } from './permission-to-hide-silently.directive';
import { MustBeConnectedToDisplayDirective } from './must-be-connected-to-display.directive';
import { MustBeDisconnectedToDisplayDirective } from './must-be-disconnected-to-display.directive';

@NgModule({
  declarations: [
    PermissionToDisplayDirective,
    PermissionToDisplaySilentlyDirective,
    PermissionToHideSilentlyDirective,
    PermissionToEnableDirective,
    MustBeConnectedToDisplayDirective,
    MustBeDisconnectedToDisplayDirective
  ],
  imports: [
    CommonModule
  ],
  exports: [
    PermissionToDisplayDirective,
    PermissionToDisplaySilentlyDirective,
    PermissionToHideSilentlyDirective,
    PermissionToEnableDirective,
    MustBeConnectedToDisplayDirective,
    MustBeDisconnectedToDisplayDirective
  ],
})
export class DirectivesModule { }
