<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>email</title>
  </head>

  <body style="margin: 0; padding: 0; background-color: #f3f3f3; font-family: '微軟正黑體', Helvetica, Arial, sans-serif;">
    <div
      style="
        max-width: 486px;
        margin: 0 auto;
        color: grey;
        background-color: #fff;
        padding-bottom: 10px;
        border-radius: 5px;
        overflow: hidden;
      "
    >
      <img src="https://twdd.tw/assets/img/email/logo-background.png" alt="top-bar" style="width:100%;" />
      <div style="margin: 20px auto; width: 220px;">
        <img style="width: 220px" src="https://twdd.tw/assets/img/email/car.png" alt="" />
      </div>
      <div style="padding-left: 15px; padding-right: 15px; color: #2f2f2f;">
        <!-- title -->
        <div style="font-size: 20px; font-weight: 500">親愛的貴賓 您好</div>
        <p style="margin-top: 5px">您已成功預約鐘點代駕服務！</p>

        <!-- box -->
        <div style="
            border: 1px solid #e7e7e7;
            padding: 10px;
            border-radius: 5px;
            position: relative;
          "
        >
          <div style="font-size: 15px; color: #00a3dd; margin-bottom: 5px">
            專業駕駛
          </div>

          <div>
            <div style="font-size: 17px; margin-right: 5px; display: inline-block;">{{$driver->DriverName}}</div>
            <div style="display: inline-block; vertical-align: middle; margin-right: 5px;">
              @for ($i=1;$i<$driver->stars;$i++)
                <img width="23" height="23" style="width: 23px; height: 23px;" src="https://twdd.tw/assets/img/email/star-all.png" alt="star-all" />
              @endfor
              
              @if ($i - $driver->stars <= 0.5)
                <img width="23" height="23" style="width: 23px; height: 23px;" src="https://twdd.tw/assets/img/email/star-all.png" alt="star-all" />
              @else
                <img width="23" height="23" style="width: 23px; height: 23px;" src="https://twdd.tw/assets/img/email/star-half.png" alt="star-half" />
              @endif
            </div>
            <div style="font-size: 17px; display: inline-block;">({{$driver->stars}})</div>
          </div>
            
        </div>

        <!-- box -->
        <div style="
            border: 1px solid #e7e7e7;
            padding: 10px;
            border-radius: 5px;
            position: relative;
            font-size: 15px;
            margin: 15px auto;
          "
        >任務編號 <span>{{$calldriverTaskMap->id}}</span> 
        </div>
        <div>
          <div style="margin-bottom:10px">
            <span>服務時長</span>
            <span style="float: right; text-align: right;">{{$calldriverTaskMap->blackhat_detail->type == 1 ? '5小時 鐘點代駕 (尊榮黑帽客)' : '8小時 鐘點代駕 (尊榮黑帽客)'}}</span>
          </div>
          <div style="margin-bottom:10px">
            <span>出發時間</span>
            <span style="float: right; text-align: right;">{{ \Carbon\Carbon::parse($calldriverTaskMap->blackhat_detail->start_date)->format('Y-m-d H:i')}}</span>
          </div>

          <hr style="border-top: 1px solid #e7e7e7; margin: 15px auto;">
          
          <div style="margin-bottom:10px">
            <span>出發/結束地</span>
            <span style="float: right; text-align: right;">{{$calldriverTaskMap->calldriver->addrKey}}</span>
          </div>
          <div style="margin-bottom:10px">
            <span>備註</span>
            <span style="float: right; text-align: right;">{{$calldriverTaskMap->calldriver->UserRemark}}</span>
          </div>
          
          <div style="clear:both"></div>
        </div>
      </div>
      
      <img
        width="486"
        src="https://maps.googleapis.com/maps/api/staticmap?zoom=17&markers=color:blue%7Clabel:S%7Csize:mid%7Cicon:https://www.twdd.tw/assets/img/map-car.png|{{$calldriverTaskMap->calldriver->lat}},{{$calldriverTaskMap->calldriver->lon}}&size=486x320&maptype=roadmap&format=png&key={{env('GOOGLE_API_KEY')}}" 
        alt="Google map" 
      />

    </div>
    <footer
        style="
          max-width: 486px;
          margin: 0 auto 20px;
          text-align: center;
          padding: 5px 10px;
          font-size: 15px;
          color: #c4c4c4;
          overflow: hidden;
        "
      >
        <p style="text-align: justify;">服務明細可於台灣代駕APP中的服務紀錄查詢。提醒您，本信件為系統發送，請勿直接點選回覆。如有疑問，您可以透過24小時客服專線0800-00-5209或客服信箱service@twdd.com.tw 與我們聯繫。</p>
        <a href="https://twdd.tw" style="color:#00a3dd; text-decoration: none; font-size: 15px;">twdd.tw</a>
      </footer>

      
  </body>
</html>