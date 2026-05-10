import { useNavigate } from 'react-router-dom';
import { SquarePen, Search } from 'lucide-react';
import { useAppSelector } from '../../../shared/store/hooks';
import { setNotesActiveSearch } from '../../../shared/store/uiSlice';
import { SearchBar } from '../../../shared/components';
import { useSearchState, useMobileSearch } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
import { NotesList } from './NotesList';
import styles from './NotesList.module.css';

export function NotesListShell() {
  const navigate = useNavigate();
  const activeSearch = useAppSelector((state) => state.ui.notesActiveSearch);

  const {
    input: searchInput,
    setInput: setSearchInput,
    handleSearch,
    handleClear,
  } = useSearchState(activeSearch, setNotesActiveSearch);

  const {
    searchRef,
    searchOpen,
    searchValue,
    setSearchValue,
    openSearch,
    handleSubmit,
    handleClose,
  } = useMobileSearch(activeSearch, setNotesActiveSearch);

  useActionBar({
    buttons: [
      { icon: SquarePen, label: 'New note', primary: true, onPress: () => navigate('/notes/new') },
      { icon: Search, label: 'Search', onPress: openSearch },
    ],
    input: searchOpen
      ? {
          ref: searchRef,
          value: searchValue,
          placeholder: 'Search notes…',
          onChange: setSearchValue,
          onSubmit: handleSubmit,
          onClose: handleClose,
        }
      : null,
  });

  return (
    <div>
      <div className={styles.header}>
        <h2 className={styles.headerTitle}>Notes</h2>
        <button
          className={`btn btn-primary hide-on-mobile ${styles.headerBtn}`}
          onClick={() => navigate('/notes/new')}
          aria-label="New note"
        >
          <SquarePen size={16} strokeWidth={1.75} />
        </button>
      </div>

      <SearchBar
        className="hide-on-mobile"
        value={searchInput}
        onChange={setSearchInput}
        onSearch={handleSearch}
        onClear={handleClear}
        placeholder="Search notes..."
        hasActiveSearch={activeSearch !== null}
      />

      <NotesList />
    </div>
  );
}
