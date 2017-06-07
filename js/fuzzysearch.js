$(document).ready(function() {
   var list = [];

   // prepapre options for fuzzy lib
   var fuzzy_options = {
      pre: "<b>",
      post: "</b>",
      extract: function(el) {
         return el.title;
      }
   };

   // when the shortcut for fuzzy is called
   $(document).bind('keyup', 'alt+ctrl+g', function() {
      console.log('start fuzzy search');

      // retrieve html of fuzzy input
      $.get(CFG_GLPI.root_doc+'/ajax/fuzzysearch.php', {
         'action': 'getHtml'
      }, function(html) {
         $(document.body).append(html);

         // retrieve current menu data
         $.getJSON(CFG_GLPI.root_doc+'/ajax/fuzzysearch.php', {
            'action': 'getList'
         }, function(data) {
            list = data;
         });

         $(document).bind('keyup', function(key) {
            switch (key.key) {
               case "Escape":
                  removeFuzzy();
                  break;

               case "ArrowUp":
                  selectPrev();
                  break;

               case "ArrowDown":
                  selectNext();
                  break;

               case "Enter":
                  // find url, if one selected, go for it, else try to find first element
                  var url = $("#fuzzysearch .results .selected a").attr('href');
                  if (url == undefined) {
                     url = $("#fuzzysearch .results li:first a").attr('href');
                  }
                  if (url != undefined) {
                     document.location = url;
                  }
                  break;
            }
         })

         // when a key is pressed in fuzzy input, launch match
         $("#fuzzysearch input").focus()
            .bind('keyup', function(key) {
               if (key.key != "Escape"
                   && key.key != "ArrowUp"
                   && key.key != "ArrowDown"
                   && key.key != "Enter") {
                  startFuzzy();
               }
            });

         setTimeout(function() {
            if ($("#fuzzysearch .results li").length == 0) {
               startFuzzy();
               selectFirst();
            }
         }, 100);
      });
   });

   /**
    * Call Fuzzy lib and match the current input with the menu list
    */
   var startFuzzy = function() {
      // retrieve input
      var input_text = $("#fuzzysearch input").val();

      //clean old results
      $("#fuzzysearch .results").empty();

      // launch fuzzy search on this list
      var results = fuzzy.filter(input_text, list, fuzzy_options);

      // append new results
      results.map(function(el) {
         //console.log(el.string);
         $("#fuzzysearch .results")
            .append("<li><a href='"+CFG_GLPI.root_doc+el.original.url+"'>"+el.string+"</a></li>")
      });
   };

   /**
    * Clean generated Html
    */
   var removeFuzzy = function() {
      $("#fuzzysearch, .fuzzymodal").remove();
   };

   /**
    * Select the first element in the results list
    */
   var selectFirst = function() {
      $("#fuzzysearch .results li:first()").addClass("selected");
   }

   /**
    * Select the last element in the results list
    */
   var selectLast = function() {
      $("#fuzzysearch .results li:last()").addClass("selected");
   }

   /**
    * Select the next element in the results list.
    * If no selected, select the first.
    */
   var selectNext = function() {
      if ($("#fuzzysearch .results .selected").length == 0) {
         selectFirst();
      } else  {
         $("#fuzzysearch .results .selected:not(:last-child)")
            .removeClass('selected')
            .next()
            .addClass("selected");
      }

      scrollToSelected();
   };

   /**
    * Select the previous element in the results list.
    * If no selected, select the last.
    */
   var selectPrev = function() {
      if ($("#fuzzysearch .results .selected").length == 0) {
         selectLast();
      } else  {
         $("#fuzzysearch .results .selected:not(:first-child)")
            .removeClass('selected')
            .prev()
            .addClass("selected");
      }
      scrollToSelected();
   };

   /**
    * Force scroll to the selected element in the results list
    */
   var scrollToSelected = function() {
      var results = $("#fuzzysearch .results");
      var selected = results.find('.selected');

      results.scrollTop(results.scrollTop() + selected.position().top - results.height()/2 + selected.height()/2);
   };
});
