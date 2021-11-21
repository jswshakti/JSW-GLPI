module.exports = {
   "presets": ["@babel/env"],
   "plugins": [
      "@babel/plugin-transform-runtime"
   ],
   "env": {
      "test": {
         "presets": ["@babel/env"],
         "plugins": [
            "@babel/plugin-proposal-class-properties",
            "@babel/plugin-transform-modules-commonjs"
         ]
      }
   }
};
