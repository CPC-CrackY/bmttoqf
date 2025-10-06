import { Component, OnInit } from '@angular/core';
import { Link } from '../../../ENEDIS-core/components/navbar/link';

@Component({
  selector: 'app-help',
  templateUrl: './help.component.html',
  styleUrls: ['./help.component.scss']
})
export class HelpComponent implements OnInit {

  links: Link[] = [];

  constructor() { }

  ngOnInit(): void {
  }

}
