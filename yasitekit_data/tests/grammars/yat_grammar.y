%start statements

statements : statement statements %str{ @1 @2 }
            |
            ;
statement : HTML %str{ @1 }
          | Y_TEXT %str{ @1 }
          | PHP %str{ @1 }
          | yat_simple  %str{ @1 }
          | yat_php_prefix
          | yat_yatheme %str{ @1 }
          | error %str{ @1 }
          ;
yat_simple : Y_OPEN_YBRACE yat_simple_command Y_CLOSE_YBRACE %str{ @2 }
          ;
yat_simple_command : Y_COMMENT Y_TEXT
          | Y_YATHEME Y_TEXT (A) %php{ $__context->yatheme = @2; }  
          | Y_TEST Y_TEXT (V) %php{ @@ = $__context->test_variable(V); }
          | Y_GUARDS Y_TEXT (A) %php{ $__context->guards = @2; }
          | Y_YATEMPLATE Y_YATEMPLATE_FILE (A)
              %php{
                $__context->add_file_name(@2);
                $__context->template_file = @2;
              }
          | Y_YATEMPLATE_CONTENT
              %php{ @@ = $__context->yatemplate_content ? $__context->yatemplate_content : '{: yatemplate-content :}'; }
          | Y_AUTHORITY Y_TEXT
              %php{ $__context->authority = trim(@2); $__context->set_variable('required_authority', trim(@2)); }
          | Y_ERRORS Y_TEXT
              %php{ $__context->errors = @2; }
          | Y_ERRORS Y_EMAIL Y_TEXT
              %php{ $__context->errors = @2; $__context->errors_email = @3; }
          | Y_INCLUDE Y_INCLUDE_FILE (A)
              %php{
                $__context->add_file_name(@2);
                @@ = $__context->include_file(@2);
              }
          | Y_ATTRIBUTE (A) Y_TEXT (D) %php{ @@ = $__context->render_attribute(A, D); }
          | Y_ATTRIBUTE %php{ @@ = $__context->render_attribute(@1); }
          | Y_META (M) Y_TEXT (T) %php{ $__context->add_meta(M, T); }
          | Y_JAVASCRIPT %php{ $__context->add_javascript(@1); }
          | Y_CSS (C) Y_TEXT (T) %php{ $__context->add_css(C, T); }
          | Y_CSS (C) %php{ $__context->add_css(C); }
          | Y_RENDER
            %php{
              switch (@1) {
                case 'meta': @@ = '{:-meta-:}'; break;
                case 'css': @@ = '{:-css-:}'; break;
                case 'javascript': @@ = '{:-javascript-:}'; break;
                default: throw new Exception("Unable to render '@1' - Illegal value");
              }
            }
          ;
yat_php_prefix : Y_OPEN_YBRACE Y_PHP_PREFIX Y_CLOSE_YBRACE Y_TEXT (T) Y_OPEN_YBRACE Y_END_PHP_PREFIX Y_CLOSE_YBRACE
            %php{ $__context->add_to_php_prefix(T); }
          ;
yat_yatheme : Y_OPEN_YBRACE Y_YATHEME Y_YATHEME_OFF Y_CLOSE_YBRACE Y_TEXT (T)
                    Y_OPEN_YBRACE Y_YATHEME Y_YATHEME_ON Y_CLOSE_YBRACE 
                    %str{ T }
          ;
