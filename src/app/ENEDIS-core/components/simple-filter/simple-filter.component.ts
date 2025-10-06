import { Component, OnInit, Input, EventEmitter, Output } from '@angular/core';
import { ApiAzurService } from '../../services/api-azur.service';

export interface Filter {
  name: string;
  items: Item[];
  maxItems: number;
  multiple: boolean;
  color: string;
  itemPadding: string;
  countLeft: string;
  countRight: string;
}
export interface Item {
  name: string;
  filter: string;
  count?: number;
}

type color = 'bleu_enedis' | 'vert_enedis' | 'neutre' | 'vert_fonce' | 'jaune_solaire' | 'rouge' | 'bleu_moyen' | 'turquoise' | 'orange' | 'prune' | 'violet' | 'bleu_fonce' | 'taupe' | 'marron';
type position = 'left' | 'right';

@Component({
  selector: 'app-simple-filter',
  templateUrl: './simple-filter.component.html',
  styleUrls: ['./simple-filter.component.scss']
})

export class SimpleFilterComponent implements OnInit {

  @Input() filtres: string[] = [];
  @Input() apiUrl: any = null;
  @Input() maxItems: number[] = [];
  @Input() multiple: boolean[] = [];
  @Input() countPositions: position[] = [];
  @Input() countColors: color[] = [];
  @Input() default: any[] = [];
  @Input() disallowCache: boolean = false;
  @Input() reloadOnOtherFilterChange: boolean = false;

  public selectionsUtilisateur = this.apiAzurService.selectionsUtilisateur;

  @Output() changeFilter = new EventEmitter<any>();

  public filters: Filter[] = [];

  constructor(private apiAzurService: ApiAzurService) { }

  ngOnInit(): void {
    if (this.filtres) {
      this.filtres.forEach((currentName: string, index) => {

        let color: string = 'var(--taupe_400)';
        if (this.countColors[index]) color = 'var(--' + this.countColors[index] + '_400)';

        let itemPadding: string = '0 40px 0 0';
        let countLeft: string = 'inherit';
        let countRight: string = '5px';
        if (this.countPositions[index] && this.countPositions[index] === 'left') {
          itemPadding = '0 0 0 30px';
          countLeft = '5px';
          countRight = 'inherit';
        }

        if (typeof this.multiple[index] === 'undefined') {
          this.multiple[index] = true;
        }

        if (typeof this.maxItems[index] === 'undefined') {
          this.maxItems[index] = 2;
        }

        this.filters.push(
          {
            name: currentName,
            maxItems: this.maxItems[index],
            multiple: this.multiple[index],
            color,
            itemPadding,
            countLeft,
            countRight,
            items: []
          }
        );
        this.loadFilterItemsFromAPI(currentName);
        if (this.default[index]) {
          this.selectionsUtilisateur[currentName] = this.default[index];
        }
      });
    }
    this.applyChange(null);
  }

  public applyChange(currentName: any | null): void {
    this.changeFilter.emit();
    if (this.reloadOnOtherFilterChange) {
      this.loadAllFiltersItemsFromAPI(currentName);
    }
  }

  public clearAll(filter: any): void {
    this.selectionsUtilisateur[filter] = null;
    this.applyChange(null);
  }

  private loadAllFiltersItemsFromAPI(exceptThisFilter: any | null) {
    if (this.filtres) {
      this.filtres.forEach((currentName: string) => {
        if (currentName != exceptThisFilter) this.loadFilterItemsFromAPI(currentName);
      });
    }
  }

  private loadFilterItemsFromAPI(filterName: string) {

    let command = this.apiAzurService.get;
    let cachedCommand = this.apiAzurService.get;
    if (this.reloadOnOtherFilterChange) {
      command = this.apiAzurService.getFiltered;
      cachedCommand = this.apiAzurService.getFiltered;
    }

    if (this.apiUrl) {
      if (this.disallowCache || this.reloadOnOtherFilterChange) {
        if (this.reloadOnOtherFilterChange) {
          this.apiAzurService.getDelayedAndFiltered<Item[]>(`filtre:` + filterName, 100, this.apiUrl).then((data: Item[]) => {
            this.filters.forEach((element: Filter) => {
              if (element.name === filterName) {
                element.items = data;
              }
            });
          });
        } else {
          this.apiAzurService.getDelayed<Item[]>(`filtre:` + filterName, 100, this.apiUrl).then((data: Item[]) => {
            this.filters.forEach((element: Filter) => {
              if (element.name === filterName) {
                element.items = data;
              }
            });
          });
        }
      } else {
        this.apiAzurService.getOnce<Item[]>(`filtre:` + filterName, this.apiUrl).then((data: Item[]) => {
          this.filters.forEach((element: Filter) => {
            if (element.name === filterName) {
              element.items = data;
            }
          });
        });
      }
    } else {
      if (this.disallowCache || this.reloadOnOtherFilterChange) {
        if (this.reloadOnOtherFilterChange) {
          this.apiAzurService.getDelayedAndFiltered<Item[]>(`filtre:` + filterName, 100).then((data: Item[]) => {
            this.filters.forEach((element: Filter) => {
              if (element.name === filterName) {
                element.items = data;
              }
            });
          });
        } else {
          this.apiAzurService.getDelayed<Item[]>(`filtre:` + filterName, 100).then((data: Item[]) => {
            this.filters.forEach((element: Filter) => {
              if (element.name === filterName) {
                element.items = data;
              }
            });
          });
        }
      } else {
        this.apiAzurService.getOnce<Item[]>(`filtre:` + filterName).then((data: Item[]) => {
          this.filters.forEach((element: Filter) => {
            if (element.name === filterName) {
              element.items = data;
            }
          });
        });
      }
    }
  }

}
