/** @type {import('tailwindcss').Config} */
export default {
  content: ['./index.html', './src/**/*.{js,jsx}'],
  theme: {
    extend: {
      colors: {
        // Deep French Navy — the dominant brand colour.
        navy: {
          50: '#F2F5FA',
          100: '#E3EAF4',
          200: '#C3D2E6',
          300: '#93AACC',
          400: '#5C7BA8',
          500: '#35578A',
          600: '#24446F',
          700: '#1B365D',
          800: '#152A49',
          900: '#101F36',
          950: '#0A1526',
        },
        // French Red — reserved for small accents only.
        rouge: {
          50: '#FDF2F4',
          100: '#FBE0E5',
          200: '#F6C2CB',
          300: '#EE8D9E',
          400: '#E15570',
          500: '#C8102E',
          600: '#A80D26',
          700: '#8A0A1F',
          800: '#6B0818',
          900: '#4D0611',
        },
        mist: '#F4F6F8',
        ink: '#111111',
        success: '#16A34A',
      },
      fontFamily: {
        sans: ['Vazirmatn', 'IRANSansX', 'IRANSans', 'Tahoma', 'system-ui', 'sans-serif'],
      },
      fontSize: {
        // Fluid display sizes so headings breathe at every breakpoint.
        'display-sm': ['clamp(1.75rem, 1.35rem + 2vw, 2.5rem)', { lineHeight: '1.35', letterSpacing: '-0.01em' }],
        'display-md': ['clamp(2.1rem, 1.5rem + 3vw, 3.25rem)', { lineHeight: '1.3', letterSpacing: '-0.015em' }],
        'display-lg': ['clamp(2.4rem, 1.6rem + 4vw, 4.25rem)', { lineHeight: '1.25', letterSpacing: '-0.02em' }],
      },
      borderRadius: {
        soft: '16px',
        card: '20px',
        pill: '999px',
      },
      boxShadow: {
        soft: '0 1px 2px rgba(16, 31, 54, 0.04), 0 8px 24px -12px rgba(16, 31, 54, 0.10)',
        card: '0 1px 2px rgba(16, 31, 54, 0.04), 0 16px 40px -20px rgba(16, 31, 54, 0.18)',
        lift: '0 2px 4px rgba(16, 31, 54, 0.04), 0 28px 60px -24px rgba(16, 31, 54, 0.26)',
        ring: '0 0 0 1px rgba(27, 54, 93, 0.08)',
      },
      maxWidth: {
        content: '1200px',
        prose: '68ch',
      },
      transitionTimingFunction: {
        premium: 'cubic-bezier(0.22, 1, 0.36, 1)',
      },
      keyframes: {
        'accordion-down': {
          from: { height: '0', opacity: '0' },
          to: { height: 'var(--accordion-height)', opacity: '1' },
        },
        float: {
          '0%, 100%': { transform: 'translateY(0)' },
          '50%': { transform: 'translateY(-10px)' },
        },
        shimmer: {
          '0%': { transform: 'translateX(-100%)' },
          '100%': { transform: 'translateX(100%)' },
        },
      },
      animation: {
        float: 'float 7s ease-in-out infinite',
        'float-slow': 'float 11s ease-in-out infinite',
      },
    },
  },
  plugins: [],
};
