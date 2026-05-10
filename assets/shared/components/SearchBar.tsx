import React from 'react';
import { Search } from 'lucide-react';
import styles from './SearchBar.module.css';

interface SearchBarProps {
  value: string;
  onChange: (value: string) => void;
  onSearch: (query: string) => void;
  onClear: () => void;
  placeholder?: string;
  hasActiveSearch: boolean;
  className?: string;
}

export const SearchBar = React.memo(function SearchBar({
  value,
  onChange,
  onSearch,
  onClear,
  placeholder,
  hasActiveSearch,
  className,
}: SearchBarProps) {
  return (
    <div className={`${styles.wrapper}${className ? ` ${className}` : ''}`} role="search">
      <input
        type="text"
        placeholder={placeholder ?? 'Search...'}
        value={value}
        onChange={(e) => onChange(e.target.value)}
        onKeyDown={(e) => {
          if (e.key === 'Enter') onSearch((e.target as HTMLInputElement).value);
        }}
        className={styles.input}
        aria-label={placeholder ?? 'Search'}
      />
      <button
        className={`btn btn-primary ${styles.searchButton}`}
        onClick={() => onSearch(value)}
        aria-label="Search"
      >
        <Search size={16} />
      </button>
      {hasActiveSearch && (
        <button
          className={`btn btn-ghost ${styles.clearButton}`}
          onClick={onClear}
          aria-label="Clear search"
        >
          Clear
        </button>
      )}
    </div>
  );
});
