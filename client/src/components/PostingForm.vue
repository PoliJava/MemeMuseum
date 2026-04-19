<template>
  <div class="post-form-container">
    <table class="post-form-table">
      <tr>
        <td class="form-label">Name</td>
        <td><input type="text" value="Anonymous" class="form-input" /></td>
      </tr>
      <tr>
        <td class="form-label">Subject</td>
        <td><input type="text" class="form-input" /></td>
      </tr>
      <tr>
        <td class="form-label">Comment</td>
        <td><textarea class="form-textarea"></textarea></td>
      </tr>
      <tr>
        <td class="form-label">File</td>
        <td>
          <input type="file" class="file-input" />
          <button class="submit-btn">Post Thread</button>
        </td>
      </tr>
    </table>
  </div>
</template>
