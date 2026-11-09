interface Props {
  page: number;
  pages: number;
  onChange: (page: number) => void;
}

export default function Pagination({ page, pages, onChange }: Props) {
  return (
    <div className="pagination">
      <button className="btn btn-secondary" disabled={page <= 1} onClick={() => onChange(page - 1)}>
        Précédent
      </button>
      <span>
        Page {page} / {pages}
      </span>
      <button className="btn btn-secondary" disabled={page >= pages} onClick={() => onChange(page + 1)}>
        Suivant
      </button>
    </div>
  );
}
