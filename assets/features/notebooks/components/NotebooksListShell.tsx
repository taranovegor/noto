import { useNavigate } from 'react-router-dom';
import { BookPlus, Search } from 'lucide-react';
import { useAppSelector } from '../../../shared/store/hooks';
import { setNotebooksActiveSearch } from '../../../shared/store/uiSlice';
import { Toolbar } from '../../../shared/components';
import { useSearchState, useMobileSearch } from '../../../shared/hooks';
import { useActionBar } from '../../../layout/ActionBarContext';
import { PageShell } from '../../../shared/components/PageShell';
import { NotebooksList } from './NotebooksList';

export function NotebooksListShell() {
  const navigate = useNavigate();
  const activeSearch = useAppSelector((state) => state.ui.notebooksActiveSearch);

  const {
    input: searchInput,
    setInput: setSearchInput,
    handleSearch,
    handleClear,
  } = useSearchState(activeSearch, setNotebooksActiveSearch);

  const {
    searchRef,
    searchOpen,
    searchValue,
    setSearchValue,
    openSearch,
    handleSubmit,
    handleClose,
  } = useMobileSearch(activeSearch, setNotebooksActiveSearch);

  useActionBar({
    buttons: [
      {
        icon: BookPlus,
        label: 'New notebook',
        primary: true,
        onPress: () => navigate('/notebooks/new'),
      },
      { icon: Search, label: 'Search', onPress: openSearch },
    ],
    input: searchOpen
      ? {
          ref: searchRef,
          value: searchValue,
          placeholder: 'Search notebooks…',
          onChange: setSearchValue,
          onSubmit: handleSubmit,
          onClose: handleClose,
        }
      : null,
  });

  return (
    <PageShell title="Notebooks">
      <Toolbar
        className="hide-on-mobile"
        value={searchInput}
        onChange={setSearchInput}
        onSearch={handleSearch}
        onClear={handleClear}
        placeholder="Search notebooks..."
        hasActiveSearch={activeSearch !== null}
        actions={[
          { icon: BookPlus, label: 'New notebook', onClick: () => navigate('/notebooks/new') },
        ]}
      />

      <NotebooksList />
    </PageShell>
  );
}
