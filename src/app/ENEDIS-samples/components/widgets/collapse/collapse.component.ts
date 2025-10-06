import { Component, OnInit } from '@angular/core';

@Component({
  selector: 'app-collapse',
  templateUrl: './collapse.component.html',
  styleUrls: ['./collapse.component.scss']
})
export class CollapseComponent implements OnInit {

  isCollapsed = false;
  isCollapsedAnimated = false;
  isCollapsedEvent = false;
  isOpen = false;
  isCollapsedInline = false;


  message: string = '';
 
  collapsed(): void {
    this.message = 'collapsed';
  }
 
  collapses(): void {
    this.message = 'collapses';
  }
 
  expanded(): void {
    this.message = 'expanded';
  }
 
  expands(): void {
    this.message = 'expands';
  }

  
  constructor() { }

  ngOnInit() {
  }

}
