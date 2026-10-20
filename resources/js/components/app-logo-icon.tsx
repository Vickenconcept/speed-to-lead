import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg {...props} viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M13 2 4.5 13.5H11L10 22 19.5 10.5H13L13 2Z" />
        </svg>
    );
}
