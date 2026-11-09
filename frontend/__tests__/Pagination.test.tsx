import { fireEvent, render, screen } from '@testing-library/react';
import Pagination from '@/components/Pagination';

describe('Pagination', () => {
  it('désactive Précédent sur la première page', () => {
    render(<Pagination page={1} pages={3} onChange={jest.fn()} />);
    expect(screen.getByText('Précédent')).toBeDisabled();
    expect(screen.getByText('Suivant')).toBeEnabled();
  });

  it('appelle onChange avec la page suivante', () => {
    const onChange = jest.fn();
    render(<Pagination page={2} pages={3} onChange={onChange} />);
    fireEvent.click(screen.getByText('Suivant'));
    expect(onChange).toHaveBeenCalledWith(3);
  });

  it('désactive Suivant sur la dernière page', () => {
    render(<Pagination page={3} pages={3} onChange={jest.fn()} />);
    expect(screen.getByText('Suivant')).toBeDisabled();
  });
});
