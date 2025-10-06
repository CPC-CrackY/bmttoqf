import { Utilisateur } from './utilisateur';

describe('Utilisateur', () => {
  it('should create an instance', () => {
    expect(new Utilisateur(null)).toBeTruthy();
  });
});
