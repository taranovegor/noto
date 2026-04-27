import React, { useMemo } from 'react';
import type { ColorOption } from '../types';
import styles from './InlineSelect.module.css';

interface InlineSelectProps<T extends string> {
  options: ColorOption<T>[];
  value: T | undefined;
  emptyLabel: string;
  onChange: (value: T | undefined) => void;
}

function InlineSelectInner<T extends string>({
  options,
  value,
  emptyLabel,
  onChange,
}: InlineSelectProps<T>) {
  const optStyles = useMemo(() => {
    const map = new Map<string, React.CSSProperties>();
    for (const opt of options) {
      map.set(opt.value, { '--opt-bg': opt.bg, '--opt-text': opt.text });
    }
    return map;
  }, [options]);

  return (
    <div className={styles.group}>
      {!value && <span className={styles.emptyLabel}>{emptyLabel}</span>}
      {options.map((opt) => {
        const active = value === opt.value;
        return (
          <button
            key={opt.value}
            type="button"
            onClick={() => onChange(active ? undefined : opt.value)}
            className={`${styles.option} ${active ? styles.active : styles.inactive}`}
            style={optStyles.get(opt.value)}
          >
            {opt.label}
          </button>
        );
      })}
    </div>
  );
}

// `as typeof` preserves the generic signature for callers.
// React.memo alone loses it because memo's type overloads don't
// forward generics — the cast restores inference at the call site.
export const InlineSelect = React.memo(InlineSelectInner) as typeof InlineSelectInner;
