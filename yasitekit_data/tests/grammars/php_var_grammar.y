%start variable

variable : static_variable
              %php{ @@ = @1; $__context->add_variable_name(@1, 'variable 1'); }
          | non_static_variable
              %php{ @@ = @1; $__context->add_variable_name(@1, 'variable 2'); }
          ;

static_variable : class_name (C)
              %php{ $__context->add_class_name(C); }
            T_DOUBLE_COLON (D)
            referenceable_variable (V)
              %php{
                $__context->add_variable_name(C .D . $__context->pop_variable_name('static_variable 1'), 'static_variable 1');
                while ($tmp = $__context->pop_array_ref('static_variable 1')) {
                  $__context->add_variable_name(C . D . $tmp, 'static_variable 1');
                }
              }
            object_attribute_list (O)
              %php{
                @@ = C . D . V . O;
                $__context->addprefix_attr(V, 'static_variable 1');
                while ($tmp = $__context->pop_attr('static_variable 1')) {
                  $__context->add_variable_name(C . D. $tmp, 'static_variable 1');
                }
              }
/*              %php{ $__context->display_stacks('static_variable 1' ); } /* */

          | non_static_variable (C)
              %php{ $__context->add_class_name(C); }
            T_DOUBLE_COLON (D)
            referenceable_variable (V)
              %php{
                $__context->add_variable_name(C .D . $__context->pop_variable_name('static_variable 1'), 'static_variable 1');
                while ($tmp = $__context->pop_array_ref('static_variable 1')) {
                  $__context->add_variable_name(C . D. $tmp, 'static_variable 1');
                }
              }
            object_attribute_list (O)
              %php{
                @@ = C . D . V . O;
                $__context->addprefix_attr(V, 'static_variable 1');
                while ($tmp = $__context->pop_attr('static_variable 1')) {
                  $__context->add_variable_name(C . D . $tmp, 'static_variable 1');
                }
              }
/*              %php{ $__context->display_stacks('static_variable 1' ); } /* */
          ;
class_name : T_STATIC
              %str{ @1 }
          | namespace_name
              %str{ @1 }
          | T_NAMESPACE T_NS_SEPARATOR namespace_name
              %str{ @1 @2 @3 }
          | T_NS_SEPARATOR namespace_name
              %str{ @1 @2 }
          ;
namespace_name : T_NS_SEPARATOR T_STRING namespace_name %str{ @1 @2 @3 }
          | T_STRING T_NS_SEPARATOR namespace_name %str{ @1 @2 @3 }
          | T_NS_SEPARATOR T_STRING %str{ @1 @2 }
          | T_STRING %str{ @1 }
          ;

non_static_variable :  referenceable_variable (V)
              %php{
                $__context->add_variable_name($__context->pop_variable_name('static_variable 1'), 'static_variable 1');
                while ($tmp = $__context->pop_array_ref('non_static_variable 1')) {
                  $__context->add_variable_name($tmp, 'non_static_variable 1');
                }
              }
            object_attribute_list (O)
              %php{
                @@ = V . O;
                $__context->addprefix_attr(V, 'non_static_variable 1');
                while ($tmp = $__context->pop_attr('non_static_variable 1')) {
                  $__context->add_variable_name($tmp, 'non_static_variable 1');
                }
              }
/*              %php{ $__context->display_stacks('non_static_variable 1' ); } /* */
          ;
referenceable_variable :
            variable_variable (V)
              %php{ $__context->pushstack_sq_bracket('referenceable_variable 1'); }
            sq_bracket_list (R)
              %php{
                @@ = V . R;
                $v = V;
                // we need to save the raw variable name in case it is used in an attribute
                $__context->push_variable_name(V, 'referenceable_variable 1');
                // we need this here in case this referenceable_variable is an object attribute
                $__context->push_array_ref($v, 'referenceable_variable 1');
                // build rest of array references
                while ($tmp = $__context->pop_sq_bracket('referenceable_variable 1')) {
                  $v .= $tmp;
                  $__context->push_array_ref($v, 'referenceable_variable 1');
                }
                $__context->popstack_sq_bracket('referenceable_variable 1');
              }
          | variable_variable
              %str{ @1}
          ;

variable_variable : T_DOLLAR_SIGN variable_variable
            %php{
              @@ = @1 . @2;
              // this is a variable which must be defined in the local scope
              $__context->add_variable_name(@2, 'variable_variable 1');
            }
        | basic_variable
            %php{
              @@ = @1;
              // this is a variable which must be defined in the local scope
              // $__context->add_variable_name(@@, 'variable_variable 2');
            }
        ;

basic_variable : T_VARIABLE  %str{ @1 }
            | T_DOLLAR_SIGN T_LBRACE (L)
                %php{ $__context->push_context('basic_variable 2a'); }
              expr (E)
                %php{ $__context->pop_context('basic_variable 2b'); }
              T_RBRACE (R) %str{ @1 L E R }
            ;

sq_bracket_list : sq_bracket sq_bracket_list
              %str{ @1 @2 }
          |  /* empty */
          ;
sq_bracket : T_LBRACKET (L)
              %php{ $__context->push_context('sq_bracket 1'); }
            expr (E)
              %php{
                // $__context->display_stacks('sq_bracket 1');
                $__context->mergeresult_variable_name("sq_bracket 1");
                $__context->pop_context('sq_bracket 1');
              }
            T_RBRACKET (R)
              %php{
                @@ = L . E . R;
                $__context->enqueue_sq_bracket(@@, "sq_bracket 1");
              }
          ;

object_attribute_list : T_OBJECT_OPERATOR referenceable_variable (V)
              %php{
                if ($__context->verbose)  $__context->displaystack_array_ref('object_attribute_list 1');
                while ($tmp = $__context->pop_array_ref('object_attribute_list 1')) {
                  $__context->add_variable_name($tmp, 'object_attribute_list 1');
                }
                // discard saved variable name - we don't need it, because it was on the array_ref stack
                $__context->pop_variable_name('object_attribute_list 1');
                $__context->pushstack_array_ref('object_attribute_list 1');
              }
            object_attribute_list (O)
              %php{
                $__context->popstack_array_ref('object_attribute_list 1');
                @@ = @1 . V . O;
                $__context->addprefix_attr(@1 . V, 'object_attribute_list 1');
                $__context->push_attr(@1 . V, 'object_attribute_list 1');
                if ($__context->verbose) $__context->displaystack_array_ref("object_attribute_list 1: bot: @@");
                if ($__context->verbose)  $__context->displaystack_attr("object_attribute_list 1: bot: @@");
              }
          | T_OBJECT_OPERATOR T_STRING (S)
              %php{ $__context->pushstack_sq_bracket('object_attribute_list 2'); }
            sq_bracket_list (L) 
            object_attribute_list (O)
              %php{
                @@ = @1 . S . L . O;
                $prefix = @1 . S . L;
                $__context->addprefix_attr($prefix, 'object_attribute_list 2');

                // deal with array references generated above
                $s = @1 . S;
                $__context->push_attr($s, 'object_attribute_list 2');
                while ($tmp = $__context->pop_sq_bracket('object_attribute_list 2')) {
                  $s .= $tmp;
                  $__context->push_attr($s, 'object_attribute_list 2');
                }
                $__context->popstack_sq_bracket('object_attribute_list 2');
              }
          | T_OBJECT_OPERATOR T_LBRACE (L)
              %php{ $__context->push_context('object_attribute_list 3'); }
            expr (E)
              %php{ $__context->pop_context('object_attribute_list 3'); }
            T_RBRACE (R) object_attribute_list (O)
              %php{
                @@ = @1 . L .E . R . O;
                $__context->enqueue_attr(@@, 'object_attribute_list 3');
              }
          |  /* empty */
          ;


expr :       term opt_whitespace T_OP opt_whitespace expr %str{ @1 @2 @3 @4 @5 }
          | opt_whitespace term opt_whitespace %str{ @1 @2 @3 }
          ;

opt_whitespace : T_WHITESPACE %str{ @1 }
          |
          ;
func_call : T_STRING T_LPAREN func_arg_list T_RPAREN %str{ @1 @2 @3 @4 }
          | variable T_LPAREN func_arg_list T_RPAREN %str{ @1 @2 @3 @4 }
          ;
func_arg_list  : expr T_COMMA func_arg_list %str{ @1 @2 @3 }
          | expr %str{ @1 }
          |
          ;

term:       func_call %str{ @1 }
          | variable %str{ @1 }
          | T_STRING %str{ @1 }
          | T_CONSTANT_ENCAPSED_STRING %str{ @1 }
          | T_LNUMBER %str{ @1 }
          | T_DOUBLE_QUOTE encaps_list T_DOUBLE_QUOTE %str{ @1 @2 @3 }
          ;

encaps_list: encaps_var  encaps_list %str{ @1 @2 }
            |  T_ENCAPSED_AND_WHITESPACE encaps_var %str{ @1 @2 }
            | T_ENCAPSED_AND_WHITESPACE  encaps_list %str{ @1 @2 }
            |  encaps_var %str{ @1 }
            ;

encaps_var: T_VARIABLE %str{ @1 }
                %php{ $__context->add_variable_name(@1, 'encaps_var 1'); }
            |  T_VARIABLE T_LBRACKET encaps_var_offset T_RBRACKET %str{ @1 @2 @3 @4}
                %php{ $__context->add_variable_name(@1, 'encaps_var 2'); }
            |  T_VARIABLE T_OBJECT_OPERATOR T_STRING %str{ @1 @2 @3 }
                %php{
                    $__context->add_variable_name(@1, 'encaps_var 3a');
                    $__context->add_variable_name(@@, 'encaps_var 3b');
                  }
            |  T_DOLLAR_OPEN_CURLY_BRACES expr T_RBRACE %str{ @1 @2 @3 }
                %php{ $__context->add_variable_name(@@, 'encaps_var 4'); }
            |  T_DOLLAR_OPEN_CURLY_BRACES T_STRING_VARNAME T_LBRACKET expr T_RBRACKET T_RBRACE
               %str{ @1 @2 @3 @4 @5 @6 }
                %php{ $__context->add_variable_name(@@, 'encaps_var 5'); }
            |  T_CURLY_OPEN variable T_RBRACE %str{ @1 @2 @3 }
            ;

encaps_var_offset: T_STRING %str{ @1 }
            |  T_NUM_STRING %str{ @1 }
            |  T_VARIABLE %str{ @1 }
                %php{ $__context->add_variable_name(@1, 'encaps_var_offset 1'); }
            ;
