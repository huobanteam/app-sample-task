module.exports = {
  root: true,
  // https://github.com/feross/standard/blob/master/RULES.md#javascript-standard-style
  extends: 'standard',
  // required to lint *.vue files
  plugins: [
    'standard',
    'html'
  ],
  env: {
    browser: true,
    node: true,
    es6: true
  },
  parserOptions: {
    ecmaVersion: 6,
    sourceType: 'module'
  },
  // add your custom rules here
  'rules': {
    // allow paren-less arrow functions
    'arrow-parens': 0,
    // allow debugger during development
    'no-debugger': process.env.NODE_ENV === 'production' ? 2 : 0,
    // 'indent': [1, 2],
    'eol-last': 0,
    'camelcase': 0,
    'comma-dangle': [1, 'never'],
    // Doesn't work inside ES6 template strings
    'comma-spacing': 0,
    'consistent-return': 0,
    'curly': [2, 'multi-line'],
    'dot-notation': [0, {'allowKeywords': true, 'allowPattern': ''}],
    'eqeqeq': [0, 'smart'],
    'no-alert': 0,
    'no-else-return': 0,
    'strict': [0, 'global'],
    'brace-style': [1, '1tbs'],
    'comma-style': [1, 'last'],
    'key-spacing': 1,
    'no-console': 0,
    'no-lonely-if': 0,
    'no-loop-func': 1,
    'no-multi-spaces': [1, { 'exceptions': { 'VariableDeclarator': true, 'ImportDeclaration': true } }],
    'no-mixed-requires': 2,
    'no-mixed-spaces-and-tabs': [2, false],
    'no-underscore-dangle': 0,
    'no-var': 0,
    'no-duplicate-case': 0,
    'no-func-assign': 0,
    'no-proto': 0,
    'no-sequences': 1,
    // Doesn't work with ES6 classes
    // https://github.com/babel/babel-eslint/issues/8
    'no-unused-vars': [1, {'vars': 'all', 'args': 'none'}],
    // Doesn't work with await
    // https://github.com/babel/babel-eslint/issues/22
    'no-unused-expressions': 0,
    // Doesn't work with classes
    // https://github.com/babel/babel-eslint/issues/8
    'no-undef': 0,
    'no-empty': 0,
    'no-use-before-define': [2, 'nofunc'],
    'quotes': [1, 'single', 'avoid-escape'],
    'space-before-function-paren': [1, {'anonymous': 'never', 'named': 'never'}],
    'keyword-spacing': [1, {'before': true, 'after': true, 'overrides': {}}],
    'space-before-blocks': [1, 'always'],
    'space-in-brackets': [0, 'never'],
    'space-in-parens': [1, 'never'],
    'space-infix-ops': [0, {'int32Hint': true}],
    'spaced-comment': [0, 'always'],
    'semi': [0, 'always'],
    'new-cap': 0,
    'no-unreachable': [1],
    'yoda': 0,
    'one-var': 0,
    'padded-blocks': 0,
    'no-throw-literal': 0,
    'handle-callback-err': 0
  }
}
