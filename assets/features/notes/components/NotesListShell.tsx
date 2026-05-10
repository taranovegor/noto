import { useNavigate } from 'react-router-dom';
import { SquarePen, Search } from 'lucide-react';
import { useAppSelector } from '../../../shared/store/hooks';
import { setNotesActiveSearch } from '../../../shared/store/uiSlice';
import { SearchBar } from '../../../shared/components';
import { useSearchState, useMobileSearch } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
import { PageShell } from '../../../shared/components/PageShell';
import { NotesList } from './NotesList';

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
    <PageShell
      title="Notes"
      actions={
        <button
          className="btn btn-primary btn-icon hide-on-mobile"
          onClick={() => navigate('/notes/new')}
          aria-label="New note"
        >
          <SquarePen size={16} strokeWidth={1.75} />
        </button>
      }
    >
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
    </PageShell>
  );
}
