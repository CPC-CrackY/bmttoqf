import { Component, OnInit, Input } from '@angular/core';
import { Link } from '../link';

@Component({
  selector: 'app-secondary-navbars',
  templateUrl: './secondary-navbars.component.html',
  styleUrls: ['./secondary-navbars.component.scss']
})
export class SecondaryNavbarsComponent implements OnInit {

  @Input() links?: Link[];

  constructor() { }

  ngOnInit(): void {
  }

}
