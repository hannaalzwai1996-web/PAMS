import type { SVGProps } from 'react';

/**
 * Minimal hand-written icon set (outline style) — deliberately not a
 * dependency (no lucide-react/heroicons package): ARCH-0003 never called
 * for an icon library, and this app only needs a dozen or so glyphs.
 * Every icon shares the same 24x24 viewBox / stroke conventions so they
 * drop in at a consistent visual weight wherever `className="h-5 w-5"`
 * (or similar) is applied.
 */
type IconProps = SVGProps<SVGSVGElement>;

function base(props: IconProps) {
  return {
    xmlns: 'http://www.w3.org/2000/svg',
    fill: 'none',
    viewBox: '0 0 24 24',
    strokeWidth: 1.75,
    stroke: 'currentColor',
    'aria-hidden': true,
    ...props,
  } satisfies IconProps;
}

export function HomeIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M2.25 12 11.204 3.045a1.125 1.125 0 0 1 1.591 0L21.75 12M4.5 9.75V20.25a.75.75 0 0 0 .75.75h4.5v-6h4.5v6h4.5a.75.75 0 0 0 .75-.75V9.75"
      />
    </svg>
  );
}

export function UsersIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z"
      />
    </svg>
  );
}

export function BookOpenIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25"
      />
    </svg>
  );
}

export function TargetIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18Zm0-4.5a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0-3a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"
      />
    </svg>
  );
}

export function CheckBadgeIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="m9 12.75 2.25 2.25 4.5-4.5m4.5 2.25a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"
      />
    </svg>
  );
}

export function GridIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z"
      />
    </svg>
  );
}

export function MenuIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
    </svg>
  );
}

export function XIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
    </svg>
  );
}

export function ChevronLeftIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
    </svg>
  );
}

export function ChevronRightIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path strokeLinecap="round" strokeLinejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
    </svg>
  );
}

export function LogoutIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M8.25 9V5.25A2.25 2.25 0 0 1 10.5 3h6a2.25 2.25 0 0 1 2.25 2.25v13.5A2.25 2.25 0 0 1 16.5 21h-6a2.25 2.25 0 0 1-2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75"
      />
    </svg>
  );
}

export function InboxIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M3.75 9.776c0-.196.02-.392.062-.584L4.674 5.19a1.875 1.875 0 0 1 1.831-1.44h11.15c.854 0 1.6.578 1.831 1.44l.86 4.002a2.25 2.25 0 0 1 .063.584v7.474a1.875 1.875 0 0 1-1.875 1.875H5.625A1.875 1.875 0 0 1 3.75 17.25zM3.75 9.75h4.5a.75.75 0 0 1 .75.75v.5a1.875 1.875 0 0 0 1.875 1.875h2.25A1.875 1.875 0 0 0 15 11v-.5a.75.75 0 0 1 .75-.75h4.5"
      />
    </svg>
  );
}

export function BuildingIcon(props: IconProps) {
  return (
    <svg {...base(props)}>
      <path
        strokeLinecap="round"
        strokeLinejoin="round"
        d="M3.75 21h16.5M4.5 3.75h15v16.5h-15zM9 21v-4.5h6V21M9 7.5h1.5V9H9zm4.5 0H15V9h-1.5zM9 11.25h1.5v1.5H9zm4.5 0H15v1.5h-1.5z"
      />
    </svg>
  );
}
