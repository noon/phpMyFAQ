    <h2>{msgInstantResponse}</h2>

    <form action="{writeSendAdress}" method="get">
        <fieldset>
            <legend>{msgSearchWord}</legend>

            <input class="inputfield" id="searchfield" type="text" name="search" size="50" value="{searchString}" />
            <input class="submit" type="submit" name="submit" value="{msgSearch}" />
            <input type="hidden" name="action" value="search" /><br />

            <label>{searchOnAllLanguages}</label>
            <input class="inputfield" type="checkbox"{checkedAllLanguages} name="langs" value="all" />

            <label>{selectCategories}</label>
            <select name="searchcategory" size="1">
            <option value="%" selected="selected">{allCategories}</option>
            {printCategoryOptions}
            </select>

        </fieldset>
    </form>

    <div id="instantresponse">
    {printInstantResponse}
    </div>