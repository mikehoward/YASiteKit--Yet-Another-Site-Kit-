/*
%start variable

variable : static_variable %str{ @1 }
          %php{ $__context->pop_tmp('variable 1'); }
      | non_static_variable %str{ @1 }
          %php{ $__context->pop_tmp('variable 1'); }
      ;

static_variable : class_name (C)
          %php{
            $__context->push_class_name(C);
          }
        T_DOUBLE_COLON (D) a_variable (V ) %str{ C D V }
          %php{
            while ($v = $__context->pop_variable_name('static_variable 1'); {
              $__context->push_save($v, \"transferring $v to save_stack\");
            }
            while ($v = $__context->pop_save('static_variable 2') {
              $__context->push_variable_name(C . D . $v, \"prepending class name to $v\");
            }
          }
      | a_variable (C)
          %php{
              $__context->push_class_name(@1);
              $__context->display_variable_name());
              while ($v = $__context->pop_variable_name('static_variable 3')) {
                $__context->push_save($v, \"saving variable req for class name $v\");
              }
            }
        T_DOUBLE_COLON (D) a_variable (V) %str{ @1 @3 @4 }
          %php{
            while ($v = $__context->pop_variable_name('static_variable 4'))) {
              $__context->push_save(C . D . $v, \"transferring $v to save_stack\");
            }
            while ($v = $__context->pop_save('static_variable 5')) {
              $__context->push_variable_name($v, \"prepending class name to $v\");
            }
          }
      ;
class_name : T_STATIC %str{ @1 }
      | namespace_name %str{ @1 }
      | T_NAMESPACE T_NS_SEPARATOR namespace_name %str{ @1 @2 @3 }
      | T_NS_SEPARATOR namespace_name %str{ @1 @2 }
      ;
namespace_name : T_STRING %str{ @1 }
      | T_STRING T_NS_SEPARATOR namespace_name %str{ @1 @2 @3 }
      ;

non_static_variable : a_variable %str{ @1 }
          %php{ $__context->push_tmp(@@, 'non_static_variable 1'); }
      ;

a_variable :
      variable_variable attribute_or_array_list %str{ @1 @2 }
          %php{
              $__context->display_stacks(\"a_variable 1 (@1) - before stack adjust\");
              while ($v = $__context->pop_attr('a_variable 1 / unwinding attr stack')) {
                $v = $__context->pop_tmp('a_variable 1 / getting saved var') . $v;
                $__context->push_variable_name($v, \"a_variable 1 / $v \");
                $__context->push_tmp($v, \"a_variable 1 / $v \");
              }
              $__context->display_stacks(\"a_variable 1 - after building attributes\");
              while ($v = $__context->pop_array_ref('a_variable 1 / unwinding array refs')) {
                $root_var = $__context->pop_tmp(\"a_variable 1 / retreiving base variable\") . $v;
                $__context->push_tmp($root_var, \"a_variable 1 / saving array refs\");
                $__context->push_variable_name($root_var, \"a_variable 1 / adding array refs\");
              }
              $__context->pop_tmp('a_variable 1');
              $__context->display_stacks('a_variable 1 after stack adjustment');
           }
      | variable_variable %str{ @1 }
          %php{
            $__context->display_stacks(\"a_variable 2 (@1) before stack adjust\");
            $__context->pop_tmp('a_variable 2');
            $__context->display_stacks('a_variable 2 after stack adjustment');
          }
      ;

variable_variable : T_DOLLAR_SIGN variable_variable %str{ @1 @2 }
          %php{
            if ($__context->top_tmp() == @2) {
              $__context->discard_and_push_var(@@, ' variable_variable 1a');
            } else {
              $__context->push_tmp(@@, 'variable_variable 1b');
              $__context->push_variable_name(@@, 'variable_variable 1c');
            }
          }
      | basic_variable %str{ @1 }
          %php{
            $__context->push_tmp(@@, ' variable_variable 2a');
            $__context->push_variable_name(@@, 'variable_variable 2b');
          }
      ;

array_ref : variable_variable array_ref_list
          %str{ @1 @2 }
      ;
array_ref_list : array_ref array_ref_list
          %str{ @1 @2 }
      | array_ref
          %str{ @1 }
      |
      ;
array_reference : T_LBRACKET expr T_RBRACKET
          %str{ @@ @1 @2 @3 }
      ;

attribute_or_array_list : attribute_or_array attribute_or_array_list %str{ @1 @2 }
      | attribute_or_array %str{ @1 }
      ;

attribute_or_array : object_attribute
        %str{ @1 }
        %php{ $__context->push_attr(@1, 'obj attr'); }
      | array_reference
        %str{ @1 }
        %php{ $__context->enqueue_array_ref(@1, 'array ref'); }
      ;

object_attribute_list : object_attribute object_attribute_list %str{ @1 @2 }
      | object_attribute %str{ @1 }
      ;

object_attribute : T_OBJECT_OPERATOR a_variable %str{ @1 @2 }
      | T_OBJECT_OPERATOR T_STRING %str{ @1 @2 }
      | T_OBJECT_OPERATOR T_LBRACE expr T_RBRACE %str{ @1 @2 @3 @4 }
      ;
*/
