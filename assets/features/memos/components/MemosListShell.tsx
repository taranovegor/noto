import { useNavigate } from 'react-router-dom';
import { SquarePen, Search } from 'lucide-react';
import { useAppSelector } from '../../../shared/store/hooks';
import { setMemosActiveSearch } from '../../../shared/store/uiSlice';
import { SearchBar } from '../../../shared/components';
import { useSearchState, useMobileSearch } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
import { PageShell } from '../../../shared/components/PageShell';
import { MemosList } from './MemosList';

export function MemosListShell() {
  const navigate = useNavigate();
  const activeSearch = useAppSelector((state) => state.ui.memosActiveSearch);

  const {
    input: searchInput,
    setInput: setSearchInput,
    handleSearch,
    handleClear,
  } = useSearchState(activeSearch, setMemosActiveSearch);

  const {
    searchRef,
    searchOpen,
    searchValue,
    setSearchValue,
    openSearch,
    handleSubmit,
    handleClose,
  } = useMobileSearch(activeSearch, setMemosActiveSearch);

  useActionBar({
    buttons: [
      { icon: SquarePen, label: 'New memo', primary: true, onPress: () => navigate('/memos/new') },
      { icon: Search, label: 'Search', onPress: openSearch },
    ],
    input: searchOpen
      ? {
          ref: searchRef,
          value: searchValue,
          placeholder: 'Search memos…',
          onChange: setSearchValue,
          onSubmit: handleSubmit,
          onClose: handleClose,
        }
      : null,
  });

  return (
    <PageShell
      title="Memos"
      actions={
        <button
          className="btn btn-primary btn-icon hide-on-mobile"
          onClick={() => navigate('/memos/new')}
          aria-label="New memo"
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
        placeholder="Search memos..."
        hasActiveSearch={activeSearch !== null}
      />

      <MemosList />
    </PageShell>
  );
}
