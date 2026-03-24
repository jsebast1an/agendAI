import { useEffect, useRef } from 'react';

/**
 * ScrollReveal — triggers CSS animation when element scrolls into view.
 *
 * Props:
 *  - animation: 'up' | 'left' | 'right' | 'scale' | 'fade' (default 'up')
 *  - delay: ms delay before animation plays (default 0)
 *  - threshold: 0-1 visibility ratio to trigger (default 0.15)
 *  - className: extra classes
 *  - as: wrapper element tag (default 'div')
 *  - children
 */
export default function ScrollReveal({
    animation = 'up',
    delay = 0,
    threshold = 0.15,
    className = '',
    as: Tag = 'div',
    children,
    ...rest
}) {
    const ref = useRef(null);

    useEffect(() => {
        const el = ref.current;
        if (!el) return;

        const observer = new IntersectionObserver(
            ([entry]) => {
                if (entry.isIntersecting) {
                    if (delay > 0) {
                        el.style.animationDelay = `${delay}ms`;
                    }
                    el.classList.add('revealed');
                    observer.unobserve(el);
                }
            },
            { threshold }
        );

        observer.observe(el);
        return () => observer.disconnect();
    }, [delay, threshold]);

    return (
        <Tag
            ref={ref}
            className={`reveal reveal-${animation} ${className}`}
            {...rest}
        >
            {children}
        </Tag>
    );
}
