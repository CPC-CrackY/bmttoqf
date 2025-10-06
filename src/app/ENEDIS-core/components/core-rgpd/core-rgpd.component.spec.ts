import { ComponentFixture, TestBed } from '@angular/core/testing';

import { CoreRgpdComponent } from './core-rgpd.component';

describe('CoreRgpdComponent', () => {
  let component: CoreRgpdComponent;
  let fixture: ComponentFixture<CoreRgpdComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ CoreRgpdComponent ]
    })
    .compileComponents();

    fixture = TestBed.createComponent(CoreRgpdComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
