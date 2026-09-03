import React from 'react';

interface SkeletonProps {
  variant?: 'text' | 'card';
  short?: boolean;
  tiny?: boolean;
  className?: string;
  style?: React.CSSProperties;
}

export function Skeleton({ variant = 'text', short, tiny, className, style }: SkeletonProps) {
  const classes = [
    'skeleton',
    variant === 'text' && 'skeleton-text',
    variant === 'card' && 'skeleton-card',
    short && 'short',
    tiny && 'tiny',
    className,
  ]
    .filter(Boolean)
    .join(' ');

  return <div className={classes} style={style} />;
}
