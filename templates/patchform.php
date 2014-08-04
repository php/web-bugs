 <tr>
  <th class="form-label_left">
   Patch name
  </th>
  <td class="form-input">
   <p class="cell_note">
    The patch name must be shorter than 80 characters and it must only contain alpha-numeric characters, dots, underscores or hyphens.
   </p>
   <input type="text" maxlength="80" size="40" name="in[patchname]" value="<?php echo clean($patchname) ?>"><br>
  </td>
 </tr>
 <tr>
  <th class="form-label_left">
   Patch file:
   <p class="cell_note">
    A patch file created using <kbd>git diff</kbd> (unified diff format)
   </p>
  </th>
  <td class="form-input">
   <input type="file" name="patchfile" value="<?php echo clean($patchfile) ?>">
  </td>
 </tr>
