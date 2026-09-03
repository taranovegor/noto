import React, { useState, useRef, useEffect, useCallback } from 'react';
import { MoreVertical, LucideIcon } from 'lucide-react';
import styles from './Menu.module.css';

interface MenuItemConfig {
  label: string;
  onClick: () => void;
  danger?: boolean;
}

interface MenuProps {
  items: MenuItemConfig[];
  triggerIcon?: LucideIcon;
  triggerLabel?: string;
}

export function Menu({
  items,
  triggerIcon: TriggerIcon = MoreVertical,
  triggerLabel = 'More options',
}: MenuProps) {
  const [open, setOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    if (!open) return;
    const handler = (e: MouseEvent) => {
      if (menuRef.current && !menuRef.current.contains(e.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', handler);
    return () => document.removeEventListener('mousedown', handler);
  }, [open]);

  const close = useCallback(() => setOpen(false), []);

  return (
    <div className={styles.menu} ref={menuRef}>
      <button
        type="button"
        className={styles.trigger}
        onClick={(e) => {
          e.preventDefault();
          e.stopPropagation();
          setOpen((v) => !v);
        }}
        aria-label={triggerLabel}
      >
        <TriggerIcon size={18} strokeWidth={1.75} />
      </button>
      {open && (
        <div className={styles.dropdown}>
          {items.map((item, i) => (
            <button
              key={i}
              type="button"
              className={[
                styles.item,
                item.danger && styles.danger,
                item.danger && !items[i - 1]?.danger && styles.dangerDivider,
              ]
                .filter(Boolean)
                .join(' ')}
              onClick={(e) => {
                e.preventDefault();
                e.stopPropagation();
                item.onClick();
                close();
              }}
            >
              {item.label}
            </button>
          ))}
        </div>
      )}
    </div>
  );
}
