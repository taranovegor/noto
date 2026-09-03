import React from 'react';
import { Search, X } from 'lucide-react';
import styles from './Toolbar.module.css';

interface ToolbarAction {
  icon: React.ComponentType<{ size?: number; strokeWidth?: number }>;
  label: string;
  onClick: () => void;
}

interface ToolbarProps {
  value: string;
  onChange: (value: string) => void;
  onSearch: (query: string) => void;
  onClear: () => void;
  placeholder?: string;
  hasActiveSearch: boolean;
  actions?: ToolbarAction[];
  className?: string;
}

export function Toolbar({
  value,
  onChange,
  onSearch,
  onClear,
  placeholder,
  hasActiveSearch,
  actions = [],
  className,
}: ToolbarProps) {
  return (
    <div className={`${styles.row}${className ? ` ${className}` : ''}`} role="search">
      <div className={styles.searchField}>
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
        {hasActiveSearch && (
          <button
            type="button"
            className={styles.iconButton}
            onClick={onClear}
            aria-label="Clear search"
          >
            <X size={16} strokeWidth={1.75} />
          </button>
        )}
        <button
          type="button"
          className={styles.iconButton}
          onClick={() => onSearch(value)}
          aria-label="Search"
        >
          <Search size={16} strokeWidth={1.75} />
        </button>
      </div>
      {actions.map((action, i) => (
        <button
          key={i}
          type="button"
          className={styles.actionButton}
          onClick={action.onClick}
          aria-label={action.label}
        >
          <action.icon size={16} strokeWidth={1.75} />
        </button>
      ))}
    </div>
  );
}
