import { NgModule } from '@angular/core';
import { CommonModule } from '@angular/common';

import { EnedisSamplesRoutingModule } from './enedis-samples-routing.module';

import { WidgetsModule } from './components/widgets/widgets.module';

@NgModule({
  declarations: [],
  imports: [
    CommonModule,
    WidgetsModule,
    
    EnedisSamplesRoutingModule,
  ],
  exports: []
})
export class EnedisSamplesModule { }
