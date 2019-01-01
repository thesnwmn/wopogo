<div class="row">
  <div class="eight columns">
    <label>Date (blank for today)</label>
    <input type="date" id="date" class="u-full-width native-timestamp" name="timestamp">
    <div class="fallback-timestamp">
        <select id="day" name="day"></select>
        <select id="month" name="month">
          <option value="" selected>month</option>
          <option value="01">January</option>
          <option value="02">February</option>
          <option value="03">March</option>
          <option value="04">April</option>
          <option value="05">May</option>
          <option value="06">June</option>
          <option value="07">July</option>
          <option value="08">August</option>
          <option value="09">September</option>
          <option value="10">October</option>
          <option value="11">November</option>
          <option value="12">December</option>
        </select>
        <select id="year" name="year"></select>
    </div>
  </div>
  <div class="four columns">
    <label>Time (optional)</label>
    <div class="nowrap">
      <select id="hour" name="hour" placeholder="hh"></select> :
      <select id="minute" name="minute"></select>
    </div>
  </div>
</div>
