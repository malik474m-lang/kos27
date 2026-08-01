module.exports = {
  content: [
    './index.php',
    './_admin/**/*.php',
    './_api/**/*.php',
    './includes/**/*.php',
    './pages/**/*.php',
    './data/**/*.php'
  ],
  theme: {
    extend: {
      colors: {
        primary: '#1a56db',
        'primary-dark': '#1244af',
        'primary-light': '#e1effe',
        accent: '#059669',
        'accent-dark': '#047857',
        danger: '#dc2626',
        warning: '#f59e0b',
      }
    }
  },
  plugins: [],
};
