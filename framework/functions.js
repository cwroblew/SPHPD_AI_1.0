   

//
// CVS ID: $Id$
//

   function confirmAction(thisForm, msg)
   {
        var status = confirm(msg);

        if (status)
        {
            thisForm.submit();
            return true;

        } else {
            return false;
        }

   }


   // TO BE REMOVED FROM HERE (ASIF)
   function showVac(thisForm)
   {

       thisForm.step.value="2";
       thisForm.submit();

   }

   function confirmAndSwitchSubmit(formIndex, newAction, msg, cmdVal)
   {

        var ok = confirm(msg);

        if (ok)
        {
           switchSubmit(formIndex, newAction, cmdVal);

        } else {
            return false;
        }

   }

   function switchSubmit(formIndex, newAction, cmdVal)
   {
        document.forms[formIndex].action = newAction;

        document.forms[formIndex].cmd.value=cmdVal;
        document.forms[formIndex].submit();

   }
   
   
   
