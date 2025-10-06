import { NgModule } from '@angular/core';
import { BrowserModule } from '@angular/platform-browser';

import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { MyAppModule } from './my-app/my-app.module';
import { EnedisSamplesModule } from './ENEDIS-samples/enedis-samples.module';
import { EnedisCoreModule } from './ENEDIS-core/enedis-core.module';

@NgModule({
  declarations: [
    AppComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,

    MyAppModule,
    EnedisSamplesModule,
    EnedisCoreModule,
  ],
  providers: [],
  bootstrap: [AppComponent]
})
export class AppModule { }
