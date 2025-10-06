import { Component, OnInit, ComponentFactoryResolver, Input } from '@angular/core';
import { environment } from '../../../../../environments/environment';
import { Link as Link } from '../link';

@Component({
  selector: 'app-navbar-item',
  templateUrl: './navbar-item.component.html',
  styleUrls: ['./navbar-item.component.scss']
})
export class NavbarItemComponent implements OnInit {

  private varLink: any;
  app_name: string = environment.app_name;

  @Input() set link(link: Link) {
    if (link.url) {
      link.url = link.url.substring(0, 1) === '/' ? link.url.substring(1, link.url.length) : link.url;
    }
    this.varLink = link;
  }

  get link(): Link {
    return this.varLink;
  }

  keyshortcuts(): boolean | String {
    let ret: boolean | String = false;
    if (this.link.keyshortcuts) {
      ret = this.link.keyshortcuts;
    }
    return ret;
  }

  style(): any {
    let ret: any = {};
    return ret;
  }
  constructor() { }

  ngOnInit(): void {
  }


}
