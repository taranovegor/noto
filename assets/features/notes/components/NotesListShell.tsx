import { useNavigate } from 'react-router-dom';
import { useAppSelector } from '../../../shared/store/hooks';
import { setNotesActiveSearch } from '../../../shared/store/uiSlice';
import { SearchBar } from '../../../shared/components';
import { useSearchState } from '../../../shared/hooks';
import { NotesList } from './NotesList';
import styles from './NotesList.module.css';

export function NotesListShell() {
  const navigate = useNavigate();
  const activeSearch = useAppSelector((state) => state.ui.notesActiveSearch);
  const {
    input: searchInput,
    setInput: setSearchInput,
    handleSearch,
    handleClear: handleClearSearch,
  } = useSearchState(activeSearch, setNotesActiveSearch);

  return (
    <div>
      <div className={styles.header}>
        <h2 className={styles.headerTitle}>Notes</h2>
        <button className="btn btn-primary" onClick={() => navigate('/notes/new')}>
          New note
        </button>
      </div>

      <SearchBar
        value={searchInput}
        onChange={setSearchInput}
        onSearch={handleSearch}
        onClear={handleClearSearch}
        placeholder="Search notes..."
        hasActiveSearch={activeSearch !== null}
      />

      <NotesList />
    </div>
  );
}
